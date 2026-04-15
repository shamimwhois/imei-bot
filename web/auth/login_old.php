<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;
use IMEIBot\Middleware\AuthMiddleware;

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

// Initialize services
$logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/auth.log']]);
$db = Database::getInstance(
    ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
    $logger
);
$auth = new AuthMiddleware($db, $logger);

// Form processing
$error = '';
$success = '';
$identifier = '';
$password = '';
$remember = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($identifier) || empty($password)) {
            $error = 'Please fill in all fields';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            // Attempt login
            if ($auth->login($identifier, $password, $remember)) {
                $success = 'Login successful! Redirecting...';
                
                // Redirect based on user role
                $user = $auth->getCurrentUser();
                if ($user && in_array($user['role'], ['admin', 'super_admin'])) {
                    header('Location: /admin/dashboard');
                } else {
                    header('Location: /dashboard');
                }
                exit;
            } else {
                $error = 'Invalid email/username or password';
            }
        }
    }
}

// Check for remember token
$rememberToken = $_COOKIE['remember_token'] ?? '';
if ($rememberToken && !isset($_SESSION['user_id'])) {
    $tokenData = $auth->checkRememberToken($rememberToken);
    if ($tokenData) {
        $_SESSION['flash_messages']['success'][] = 'Welcome back! You have been automatically logged in.';
        
        // Redirect based on user role
        if (in_array($tokenData['role'], ['admin', 'super_admin'])) {
            header('Location: /admin/dashboard');
        } else {
            header('Location: /dashboard');
        }
        exit;
    }
}

// Generate CSRF token
$csrfToken = $auth->generateCsrfToken();
        try {
            // Find user by email or username
            $user = User::findByEmail($identifier);
            if (!$user) {
                $user = User::findByUsername($identifier);
            }

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Invalid credentials';
            } elseif ($user['status'] !== 'active') {
                $error = 'Account is not active';
            } else {
                // Generate JWT token
                $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/app.log']]);
                $jwtService = new JWTService($logger);
                
                $tokens = $jwtService->generateToken([
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'username' => $user['username']
                ]);

                // Update user with API token and last login
                User::updateApiToken($user['id'], $tokens['access_token']);
                User::updateLastLogin($user['id']);

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['access_token'] = $tokens['access_token'];
                
                if ($remember) {
                    // Set remember me cookie (30 days)
                    setcookie('remember_token', $tokens['refresh_token'], time() + (86400 * 30), '/', '', true, true);
                }

                // Redirect to dashboard or intended page
                $redirect = $_GET['redirect'] ?? '/dashboard';
                header('Location: ' . $redirect);
                exit;
            }
        } catch (\Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/app.log']]);
        $jwtService = new JWTService($logger);
        
        $tokens = $jwtService->refreshToken($_COOKIE['remember_token']);
        $payload = $jwtService->validateToken($tokens['access_token']);
        
        $user = User::findByIdAsArray($payload['user_id']);
        
        if ($user && $user['status'] === 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['access_token'] = $tokens['access_token'];
            
            // Update remember me cookie
            setcookie('remember_token', $tokens['refresh_token'], time() + (86400 * 30), '/', '', true, true);
            
            header('Location: /dashboard');
            exit;
        }
    } catch (\Exception $e) {
        // Clear invalid remember me cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-600">
                <i class="fas fa-robot text-white text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Sign in to your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or
                <a href="/auth/register" class="font-medium text-blue-600 hover:text-blue-500">
                    create a new account
                </a>
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST" action="">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? ''); ?>">
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-4">
                <div>
                    <label for="identifier" class="block text-sm font-medium text-gray-700">
                        Email or Username
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input
                            id="identifier"
                            name="identifier"
                            type="text"
                            required
                            class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter your email or username"
                            value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                        >
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter your password"
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" onclick="togglePassword()" class="text-gray-400 hover:text-gray-500">
                                <i id="password-toggle" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>
                
                <div class="text-sm">
                    <a href="/auth/forgot-password" class="font-medium text-blue-600 hover:text-blue-500">
                        Forgot your password?
                    </a>
                </div>
            </div>
            
            <div>
                <button
                    type="submit"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-sign-in-alt text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Sign in
                </button>
            </div>
            
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-gray-50 text-gray-500">Or continue with</span>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <a href="/auth/telegram" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fab fa-telegram text-blue-500"></i>
                        <span class="ml-2">Telegram</span>
                    </a>
                    
                    <a href="/auth/whatsapp" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fab fa-whatsapp text-green-500"></i>
                        <span class="ml-2">WhatsApp</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
