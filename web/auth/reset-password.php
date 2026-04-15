<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;
use IMEIBot\Middleware\AuthMiddleware;

session_start();

// Initialize services
$logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/auth.log']]);
$db = Database::getInstance(
    ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
    $logger
);
$auth = new AuthMiddleware($db, $logger);

// Get reset token
$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid reset token';
} else {
    try {
        // Find valid reset token
        $resetData = $db->fetchOne(
            "SELECT pr.user_id, u.email, pr.token, pr.expires_at 
             FROM password_resets pr 
             JOIN users u ON pr.user_id = u.id 
             WHERE pr.token = ? AND pr.expires_at > NOW() AND u.deleted_at IS NULL",
            [$token]
        );
        
        if (!$resetData) {
            $error = 'Invalid or expired reset token';
        } else {
            // Check if token has expired
            if (new DateTime($resetData['expires_at']) < new DateTime()) {
                $error = 'Reset token has expired';
            } else {
                $success = 'Password reset successful! You can now login with your new password.';
                
                // Clear the reset token
                $db->query(
                    "DELETE FROM password_resets WHERE token = ?",
                    [$token]
                );
                
                $logger->info('Password reset completed', [
                    'user_id' => $resetData['user_id'],
                    'email' => $resetData['email'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
        }
    } catch (Exception $e) {
        $error = 'Password reset failed. Please try again.';
        $logger->error('Password reset error', [
            'error' => $e->getMessage(),
            'token' => $token,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
}

// Page configuration
$title = 'Reset Password - IMEI Bot Platform';
$description = 'Set your new password for IMEI Bot Platform';
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
                Set new password
            </h2>
        </div>

        <!-- Reset Form -->
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md">
                <div class="flex">
                    <i class="fas fa-check-circle mr-2"></i>
                    <div>
                        <h3 class="text-lg font-medium text-green-800 mb-2">Password Reset Successful!</h3>
                        <p class="text-green-600"><?= htmlspecialchars($success) ?></p>
                        <div class="mt-4">
                            <a href="/auth/login" class="font-medium text-blue-600 hover:text-blue-500">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Go to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <form class="mt-8 space-y-6" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="space-y-6">
                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            New Password
                        </label>
                        <div class="mt-1">
                            <input id="password" 
                                   name="password" 
                                   type="password" 
                                   required 
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                   placeholder="Enter your new password">
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Confirm New Password
                        </label>
                        <div class="mt-1">
                            <input id="confirm_password" 
                                   name="confirm_password" 
                                   type="password" 
                                   required 
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                   placeholder="Confirm your new password">
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

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" 
                                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-key h-5 w-5 text-blue-500 group-hover:text-blue-400"></i>
                            </span>
                            Reset Password
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <!-- Security Info -->
        <div class="mt-6">
            <div class="text-center text-sm text-gray-600">
                <div class="flex items-center justify-center space-x-2">
                    <i class="fas fa-shield-alt text-green-500"></i>
                    <span>Secure Password Reset</span>
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

<!-- Password Requirements -->
<div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-lg shadow">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Password Requirements</h3>
    <ul class="space-y-2 text-sm text-gray-600">
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>At least 8 characters long</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Contains uppercase and lowercase letters</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Contains at least one number</span>
        </li>
        <li class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
            <span>Contains at least one special character</span>
        </li>
    </ul>
</div>

<script>
// Auto-focus on password field
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.focus();
    }
    
    // Clear form on submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Resetting...';
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/../components/layout.php';
