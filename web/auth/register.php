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
$errors = [];
$success = '';
$firstName = '';
$lastName = '';
$email = '';
$username = '';
$password = '';
$confirmPassword = '';
$agreeTerms = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $agreeTerms = isset($_POST['agree_terms']);
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($username)) {
            $errors[] = 'Please fill in all required fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        } elseif (!$agreeTerms) {
            $errors[] = 'You must agree to terms and conditions';
        } else {
            // Check if email or username already exists
            $existingUser = $db->fetchOne(
                "SELECT id FROM users WHERE email = ? OR username = ? AND deleted_at IS NULL",
                [$email, $username]
            );
            
            if ($existingUser) {
                $errors[] = 'Email or username already exists';
            } else {
                // Create new user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $verificationToken = bin2hex(random_bytes(32));
                $userUuid = bin2hex(random_bytes(16));
                
                $db->query(
                    "INSERT INTO users (uuid, username, email, password_hash, first_name, last_name, role, status, verification_token, created_at) VALUES (?, ?, ?, ?, ?, ?, 'user', 'pending', ?, NOW())",
                    [$userUuid, $username, $email, $passwordHash, $firstName, $lastName, $verificationToken]
                );
                
                // Send verification email
                $subject = 'Verify your IMEI Bot Platform account';
                $message = "Hello $firstName,\n\nPlease click the link below to verify your email address:\n\n";
                $message .= "http://localhost:8080/auth/verify?token=$verificationToken\n\n";
                $message .= "This link will expire in 24 hours.\n\n";
                $message .= "If you did not create this account, please ignore this email.\n\n";
                $message .= "Best regards,\nIMEI Bot Platform Team";
                
                $headers = "From: noreply@imei-bot.com\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                // Send verification email with error handling
                try {
                    $mailSent = @mail($email, $subject, $message, $headers);
                    if (!$mailSent) {
                        $logger->warning('Email sending failed', [
                            'email' => $email,
                            'subject' => $subject,
                            'error' => error_get_last()['message'] ?? 'Unknown error'
                        ]);
                        $success = 'Registration successful! However, there was an issue sending the verification email. Please contact support.';
                    }
                } catch (Exception $e) {
                    $logger->error('Email sending error', [
                        'email' => $email,
                        'error' => $e->getMessage()
                    ]);
                    $success = 'Registration successful! However, there was an issue sending the verification email. Please contact support.';
                }
                
                $success = 'Registration successful! Please check your email to verify your account.';
                
                $logger->info('User registered', [
                    'email' => $email,
                    'username' => $username,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $auth->generateCsrfToken();

// Page configuration
$title = 'Register - IMEI Bot Platform';
$description = 'Create your account to access IMEI generation and file downloads';
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
                <h1 class="text-3xl font-bold text-gray-900">Create Account</h1>
            </div>
            <h2 class="mt-6 text-center text-2xl font-extrabold text-gray-900">
                Join IMEI Bot Platform
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Already have an account? 
                <a href="/auth/login" class="font-medium text-blue-600 hover:text-blue-500">
                    Sign in
                </a>
            </p>
        </div>

        <!-- Registration Form -->
        <form class="mt-8 space-y-6" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="space-y-6">
                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">
                        First Name
                    </label>
                    <div class="mt-1">
                        <input id="first_name" 
                               name="first_name" 
                               type="text" 
                               required 
                               value="<?= htmlspecialchars($firstName) ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Enter your first name">
                    </div>
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">
                        Last Name
                    </label>
                    <div class="mt-1">
                        <input id="last_name" 
                               name="last_name" 
                               type="text" 
                               required 
                               value="<?= htmlspecialchars($lastName) ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Enter your last name">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address
                    </label>
                    <div class="mt-1">
                        <input id="email" 
                               name="email" 
                               type="email" 
                               required 
                               value="<?= htmlspecialchars($email) ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Enter your email address">
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Username
                    </label>
                    <div class="mt-1">
                        <input id="username" 
                               name="username" 
                               type="text" 
                               required 
                               value="<?= htmlspecialchars($username) ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Choose a username">
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
                               placeholder="Create a strong password">
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <div class="mt-1">
                        <input id="confirm_password" 
                               name="confirm_password" 
                               type="password" 
                               required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Confirm your password">
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="flex items-center">
                    <input id="agree_terms" 
                           name="agree_terms" 
                           type="checkbox" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="agree_terms" class="ml-2 block text-sm text-gray-900">
                        I agree to the 
                        <a href="/terms" class="text-blue-600 hover:text-blue-500 underline">
                            Terms and Conditions
                        </a>
                        and 
                        <a href="/privacy" class="text-blue-600 hover:text-blue-500 underline">
                            Privacy Policy
                        </a>
                    </label>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <p><?= htmlspecialchars($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2"></i>
                            <p><?= htmlspecialchars($success) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-plus h-5 w-5 text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Create Account
                    </button>
                </div>
            </div>
        </form>

        <!-- Benefits -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Why Join IMEI Bot Platform?</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-mobile-alt text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">IMEI Generation</h4>
                        <p class="text-sm text-gray-600">Generate valid IMEI numbers for mobile devices</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-download text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">File Downloads</h4>
                        <p class="text-sm text-gray-600">Access mobile firmware and tools</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shield-alt text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">Secure Platform</h4>
                        <p class="text-sm text-gray-600">Enterprise-grade security and privacy</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-line text-orange-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">Analytics</h4>
                        <p class="text-sm text-gray-600">Track your IMEI generation and downloads</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus on first name field
document.addEventListener('DOMContentLoaded', function() {
    const firstNameField = document.getElementById('first_name');
    if (firstNameField) {
        firstNameField.focus();
    }
    
    // Clear form on submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating Account...';
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/../components/layout.php';
