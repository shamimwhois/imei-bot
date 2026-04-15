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
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Check if user exists
            $user = $db->fetchOne(
                "SELECT id, email FROM users WHERE email = ? AND deleted_at IS NULL",
                [$email]
            );
            
            if (!$user) {
                $error = 'No account found with this email address';
            } else {
                // Generate reset token
                $resetToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token
                $db->query(
                    "INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())",
                    [$user['id'], $resetToken, $expiresAt]
                );
                
                // Send reset email
                $resetLink = "http://localhost:8080/auth/reset-password?token=$resetToken";
                $subject = 'Reset your IMEI Bot Platform password';
                $message = "Hello,\n\n";
                $message .= "You requested to reset your password for IMEI Bot Platform.\n\n";
                $message .= "Click the link below to reset your password:\n\n";
                $message .= "$resetLink\n\n";
                $message .= "This link will expire in 1 hour.\n\n";
                $message .= "If you did not request this password reset, please ignore this email.\n\n";
                $message .= "Best regards,\nIMEI Bot Platform Team";
                
                $headers = "From: noreply@imei-bot.com\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                mail($email, $subject, $message, $headers);
                
                $success = 'Password reset link has been sent to your email address.';
                
                $logger->info('Password reset requested', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $auth->generateCsrfToken();

// Page configuration
$title = 'Forgot Password - IMEI Bot Platform';
$description = 'Reset your IMEI Bot Platform password';
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
                Reset your password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Remember your password? 
                <a href="/auth/login" class="font-medium text-blue-600 hover:text-blue-500">
                    Sign in
                </a>
            </p>
        </div>

        <!-- Reset Form -->
        <form class="mt-8 space-y-6" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="space-y-6">
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
                            <i class="fas fa-paper-plane h-5 w-5 text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Send Reset Link
                    </button>
                </div>
            </div>
        </form>

        <!-- Security Info -->
        <div class="mt-6">
            <div class="text-center text-sm text-gray-600">
                <div class="flex items-center justify-center space-x-2">
                    <i class="fas fa-shield-alt text-green-500"></i>
                    <span>Secure Process</span>
                </div>
                <div class="flex items-center justify-center space-x-2 mt-2">
                    <i class="fas fa-clock text-blue-500"></i>
                    <span>Link expires in 1 hour</span>
                </div>
                <div class="flex items-center justify-center space-x-2 mt-2">
                    <i class="fas fa-lock text-purple-500"></i>
                    <span>Encrypted Email Delivery</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Tips -->
<div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-lg shadow">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Password Reset Tips</h3>
    <ul class="space-y-2 text-sm text-gray-600">
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Check your spam folder if you don't receive the email</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Reset links are valid for 1 hour only</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Make sure to use the email address registered with your account</span>
        </li>
    </ul>
</div>

<script>
// Auto-focus on email field
document.addEventListener('DOMContentLoaded', function() {
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.focus();
    }
    
    // Clear form on submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/../components/layout.php';
