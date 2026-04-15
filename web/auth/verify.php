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

// Get verification token
$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid verification link';
} else {
    try {
        // Find user by verification token
        $user = $db->fetchOne(
            "SELECT id, username, email, verification_token_expires_at 
             FROM users 
             WHERE verification_token = ? AND email_verified = 0 AND deleted_at IS NULL",
            [$token]
        );
        
        if (!$user) {
            $error = 'Invalid or expired verification link';
        } elseif (new DateTime($user['verification_token_expires_at']) < new DateTime()) {
            $error = 'Verification link has expired';
        } else {
            // Verify user's email
            $db->query(
                "UPDATE users SET email_verified = 1, verification_token = NULL, verification_token_expires_at = NULL WHERE id = ?",
                [$user['id']]
            );
            
            $success = 'Email verified successfully! You can now login.';
            
            $logger->info('Email verified', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        $error = 'Verification failed. Please try again.';
        $logger->error('Email verification error', [
            'error' => $e->getMessage(),
            'token' => $token,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
}

// Page configuration
$title = 'Email Verification - IMEI Bot Platform';
$description = 'Verify your email address to activate your IMEI Bot Platform account';
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
                Email Verification
            </h2>
        </div>

        <!-- Verification Result -->
        <div class="mt-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <div>
                            <h3 class="text-lg font-medium text-red-800 mb-2">Verification Failed</h3>
                            <p class="text-red-600"><?= htmlspecialchars($error) ?></p>
                            <div class="mt-4">
                                <a href="/auth/login" class="font-medium text-blue-600 hover:text-blue-500">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Back to Login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md">
                    <div class="flex">
                        <i class="fas fa-check-circle mr-2"></i>
                        <div>
                            <h3 class="text-lg font-medium text-green-800 mb-2">Email Verified Successfully!</h3>
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
                <div class="bg-blue-50 border border-blue-200 text-blue-600 px-4 py-3 rounded-md">
                    <div class="flex">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        <div>
                            <h3 class="text-lg font-medium text-blue-800 mb-2">Verifying Email...</h3>
                            <p class="text-blue-600">Please wait while we verify your email address.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Security Info -->
        <div class="mt-6">
            <div class="text-center text-sm text-gray-600">
                <div class="flex items-center justify-center space-x-2">
                    <i class="fas fa-shield-alt text-green-500"></i>
                    <span>Secure Verification</span>
                </div>
                <div class="flex items-center justify-center space-x-2 mt-2">
                    <i class="fas fa-clock text-blue-500"></i>
                    <span>Links expire in 24 hours</span>
                </div>
                <div class="flex items-center justify-center space-x-2 mt-2">
                    <i class="fas fa-user-check text-purple-500"></i>
                    <span>One-time verification</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-redirect after successful verification
setTimeout(function() {
    const successElement = document.querySelector('.text-green-600');
    if (successElement && successElement.textContent.includes('Email verified successfully')) {
        setTimeout(function() {
            window.location.href = '/auth/login';
        }, 3000);
    }
}, 1000);
</script>

<?php
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/../components/layout.php';
