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

// Page configuration
$title = 'Login - IMEI Bot Platform';
$description = 'Secure login to access your IMEI Bot Platform account';
$showSidebar = false;
$pageClass = 'bg-gray-100';

// Content for layout
ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="flex items-center justify-center">
                <i class="fas fa-robot text-blue-600 text-4xl mr-3"></i>
                <h1 class="text-3xl font-bold text-gray-900">IMEI Bot Platform</h1>
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

        <!-- Login Form -->
        <form class="mt-8 space-y-6" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="space-y-6">
                <!-- Email/Username -->
                <div>
                    <label for="identifier" class="block text-sm font-medium text-gray-700">
                        Email or Username
                    </label>
                    <div class="mt-1">
                        <input id="identifier" 
                               name="identifier" 
                               type="text" 
                               required 
                               value="<?= htmlspecialchars($identifier) ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Enter your email or username"
                               autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" 
                               name="password" 
                               type="password" 
                               required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Enter your password"
                               autocomplete="current-password">
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" 
                               name="remember" 
                               type="checkbox" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Remember me for 30 days
                        </label>
                    </div>
                    <div class="text-sm">
                        <a href="/auth/forgot-password" class="font-medium text-blue-600 hover:text-blue-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-lock h-5 w-5 text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Sign in
                    </button>
                </div>
            </div>
        </form>

        <!-- Security Features -->
        <div class="mt-6">
            <div class="text-center text-sm text-gray-600">
                <div class="flex items-center justify-center space-x-2">
                    <i class="fas fa-shield-alt text-green-500"></i>
                    <span>Secure Login</span>
                </div>
                <div class="flex items-center justify-center space-x-2 mt-2">
                    <i class="fas fa-lock text-blue-500"></i>
                    <span>Encrypted Connection</span>
                </div>
                <div class="flex items-center justify-center space-x-2 mt-2">
                    <i class="fas fa-user-shield text-purple-500"></i>
                    <span>CSRF Protected</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Login Tips -->
<div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-lg shadow">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Login Tips</h3>
    <ul class="space-y-2 text-sm text-gray-600">
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Use your registered email or username</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Password must be at least 8 characters long</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Enable cookies for "Remember Me" functionality</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Account will be locked after 5 failed attempts</span>
        </li>
    </ul>
</div>

<script>
// Auto-focus on email/username field
document.addEventListener('DOMContentLoaded', function() {
    const identifierField = document.getElementById('identifier');
    if (identifierField) {
        identifierField.focus();
    }
    
    // Clear form on submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/../components/layout.php';
