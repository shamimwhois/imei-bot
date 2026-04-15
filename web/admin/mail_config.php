<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

session_start();

// Require admin authentication
$logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/admin.log']]);
$db = Database::getInstance(
    ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
    $logger
);
$auth = new AuthMiddleware($db, $logger);

// Require admin access
$auth->requireAdmin();

// Form processing
$errors = [];
$success = '';
$config = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $config = [
            'mail_driver' => trim($_POST['mail_driver'] ?? 'smtp'),
            'mail_host' => trim($_POST['mail_host'] ?? 'localhost'),
            'mail_port' => (int) ($_POST['mail_port'] ?? '587'),
            'mail_username' => trim($_POST['mail_username'] ?? ''),
            'mail_password' => trim($_POST['mail_password'] ?? ''),
            'mail_encryption' => trim($_POST['mail_encryption'] ?? 'tls'),
            'mail_from_address' => trim($_POST['mail_from_address'] ?? 'noreply@imei-bot.com'),
            'mail_from_name' => trim($_POST['mail_from_name'] ?? 'IMEI Bot Platform'),
            'mail_reply_to' => trim($_POST['mail_reply_to'] ?? ''),
            'mail_queue_enabled' => isset($_POST['mail_queue_enabled']),
            'mail_queue_connection' => trim($_POST['mail_queue_connection'] ?? ''),
            'mail_queue_table' => trim($_POST['mail_queue_table'] ?? 'mail_jobs'),
            'smtp_timeout' => (int) ($_POST['smtp_timeout'] ?? '30'),
            'smtp_auth_type' => trim($_POST['smtp_auth_type'] ?? 'login'),
            'smtp_debug_mode' => isset($_POST['smtp_debug_mode']),
            'send_test_email' => isset($_POST['send_test_email']),
            'test_email_address' => trim($_POST['test_email_address'] ?? 'test@example.com'),
        ];
        
        // Validation
        if (empty($config['mail_host'])) {
            $errors[] = 'Mail host is required';
        }
        
        if (empty($config['mail_username'])) {
            $errors[] = 'Mail username is required';
        }
        
        if (empty($config['mail_password'])) {
            $errors[] = 'Mail password is required';
        }
        
        if (!in_array($config['mail_driver'], ['smtp', 'mail', 'sendmail'])) {
            $errors[] = 'Invalid mail driver. Must be smtp, mail, or sendmail';
        }
        
        if (!filter_var($config['mail_from_address'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid from email address';
        }
        
        if (!is_numeric($config['mail_port']) || $config['mail_port'] < 25 || $config['mail_port'] > 65535) {
            $errors[] = 'Invalid port number. Must be between 25 and 65535';
        }
        
        if (!in_array($config['mail_encryption'], ['tls', 'ssl', 'none'])) {
            $errors[] = 'Invalid encryption method. Must be tls, ssl, or none';
        }
        
        if (empty($errors)) {
            // Save configuration to database
            $db->query(
                "UPDATE settings SET value = ?, updated_at = NOW() WHERE key = ?",
                [json_encode($config), date('Y-m-d H:i:s'), 'mail_config']
            );
            
            $logger->info('Mail configuration updated', [
                'config' => $config,
                'admin_id' => $_SESSION['user_id'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $success = 'Mail configuration saved successfully!';
            
            // Send test email if requested
            if ($config['send_test_email'] && !empty($config['test_email_address'])) {
                try {
                    $subject = 'IMEI Bot Platform - Test Email';
                    $message = "This is a test email from IMEI Bot Platform mail configuration.\n\n";
                    $message .= "Configuration:\n";
                    $message .= "Driver: " . $config['mail_driver'] . "\n";
                    $message .= "Host: " . $config['mail_host'] . "\n";
                    $message .= "Port: " . $config['mail_port'] . "\n";
                    $message .= "Encryption: " . $config['mail_encryption'] . "\n\n";
                    $message .= "If you receive this email, your mail configuration is working correctly.";
                    
                    $headers = "From: " . $config['mail_from_address'] . " <" . $config['mail_from_name'] . ">\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    $mailSent = @mail($config['test_email_address'], $subject, $message, $headers);
                    
                    if ($mailSent) {
                        $success .= ' Test email sent successfully to ' . $config['test_email_address'];
                    } else {
                        $errors[] = 'Failed to send test email to ' . $config['test_email_address'];
                    }
                } catch (Exception $e) {
                    $errors[] = 'Failed to send test email: ' . $e->getMessage();
                }
            }
        }
    }
} else {
    // Load current configuration from database
    $configData = $db->fetchOne("SELECT value FROM settings WHERE key = 'mail_config'");
    if ($configData) {
        $config = json_decode($configData['value'], true) ?: [];
    }
}

// Page configuration
$title = 'Mail Configuration - Admin Panel';
$description = 'Configure email settings for IMEI Bot Platform';
$showSidebar = true;
$pageClass = 'bg-gray-100';

// Content for layout
ob_start();
?>

<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-6 sm:p-6 lg:p-8">
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mail Configuration</h1>
                    <div class="flex items-center space-x-4">
                        <a href="/admin/dashboard" class="text-blue-600 hover:text-blue-500">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md mb-6">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2"></i>
                            <div>
                                <h3 class="text-lg font-medium text-green-800 mb-2">Success!</h3>
                                <p class="text-green-600"><?= htmlspecialchars($success) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md mb-6">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <div>
                                <h3 class="text-lg font-medium text-red-800 mb-2">Configuration Error</h3>
                                <?php foreach ($errors as $error): ?>
                                    <p class="text-red-600"><?= htmlspecialchars($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Configuration Form -->
                <form class="space-y-6" method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCsrfToken() ?>">
                    
                    <!-- SMTP Settings -->
                    <div class="bg-gray-50 px-4 py-5 rounded-lg">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">SMTP Settings</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Mail Driver -->
                            <div>
                                <label for="mail_driver" class="block text-sm font-medium text-gray-700">Mail Driver</label>
                                <select id="mail_driver" name="mail_driver" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="smtp" <?= $config['mail_driver'] === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                                    <option value="mail" <?= $config['mail_driver'] === 'mail' ? 'selected' : '' ?>>Mail</option>
                                    <option value="sendmail" <?= $config['mail_driver'] === 'sendmail' ? 'selected' : '' ?>>Sendmail</option>
                                </select>
                            </div>
                            
                            <!-- Mail Host -->
                            <div>
                                <label for="mail_host" class="block text-sm font-medium text-gray-700">Mail Host</label>
                                <input type="text" id="mail_host" name="mail_host" required value="<?= htmlspecialchars($config['mail_host'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="smtp.example.com">
                            </div>
                            
                            <!-- Mail Port -->
                            <div>
                                <label for="mail_port" class="block text-sm font-medium text-gray-700">Mail Port</label>
                                <input type="number" id="mail_port" name="mail_port" required value="<?= htmlspecialchars($config['mail_port'] ?? '587') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="587">
                            </div>
                            
                            <!-- Mail Username -->
                            <div>
                                <label for="mail_username" class="block text-sm font-medium text-gray-700">Mail Username</label>
                                <input type="text" id="mail_username" name="mail_username" required value="<?= htmlspecialchars($config['mail_username'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="your-email@example.com">
                            </div>
                            
                            <!-- Mail Password -->
                            <div>
                                <label for="mail_password" class="block text-sm font-medium text-gray-700">Mail Password</label>
                                <input type="password" id="mail_password" name="mail_password" required value="<?= htmlspecialchars($config['mail_password'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Your SMTP password">
                            </div>
                            
                            <!-- Mail Encryption -->
                            <div>
                                <label for="mail_encryption" class="block text-sm font-medium text-gray-700">Encryption</label>
                                <select id="mail_encryption" name="mail_encryption" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="tls" <?= $config['mail_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= $config['mail_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    <option value="none" <?= $config['mail_encryption'] === 'none' ? 'selected' : '' ?>>None</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Settings -->
                    <div class="bg-gray-50 px-4 py-5 rounded-lg">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Email Settings</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- From Address -->
                            <div>
                                <label for="mail_from_address" class="block text-sm font-medium text-gray-700">From Address</label>
                                <input type="email" id="mail_from_address" name="mail_from_address" required value="<?= htmlspecialchars($config['mail_from_address'] ?? 'noreply@imei-bot.com') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="noreply@imei-bot.com">
                            </div>
                            
                            <!-- From Name -->
                            <div>
                                <label for="mail_from_name" class="block text-sm font-medium text-gray-700">From Name</label>
                                <input type="text" id="mail_from_name" name="mail_from_name" required value="<?= htmlspecialchars($config['mail_from_name'] ?? 'IMEI Bot Platform') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="IMEI Bot Platform">
                            </div>
                            
                            <!-- Reply To -->
                            <div>
                                <label for="mail_reply_to" class="block text-sm font-medium text-gray-700">Reply To</label>
                                <input type="email" id="mail_reply_to" name="mail_reply_to" value="<?= htmlspecialchars($config['mail_reply_to'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="support@imei-bot.com">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Settings -->
                    <div class="bg-gray-50 px-4 py-5 rounded-lg">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Advanced Settings</h2>
                        
                        <div class="space-y-4">
                            <!-- SMTP Timeout -->
                            <div>
                                <label for="smtp_timeout" class="block text-sm font-medium text-gray-700">SMTP Timeout (seconds)</label>
                                <input type="number" id="smtp_timeout" name="smtp_timeout" value="<?= htmlspecialchars($config['smtp_timeout'] ?? '30') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="30">
                            </div>
                            
                            <!-- SMTP Auth Type -->
                            <div>
                                <label for="smtp_auth_type" class="block text-sm font-medium text-gray-700">SMTP Auth Type</label>
                                <select id="smtp_auth_type" name="smtp_auth_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="login" <?= $config['smtp_auth_type'] === 'login' ? 'selected' : '' ?>>Login</option>
                                    <option value="plain" <?= $config['smtp_auth_type'] === 'plain' ? 'selected' : '' ?>>Plain</option>
                                </select>
                            </div>
                            
                            <!-- SMTP Debug Mode -->
                            <div class="flex items-center">
                                <input type="checkbox" id="smtp_debug_mode" name="smtp_debug_mode" <?= $config['smtp_debug_mode'] ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="smtp_debug_mode" class="ml-2 block text-sm text-gray-900">Enable Debug Mode</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mail Queue Settings -->
                    <div class="bg-gray-50 px-4 py-5 rounded-lg">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Mail Queue Settings</h2>
                        
                        <div class="space-y-4">
                            <!-- Enable Mail Queue -->
                            <div class="flex items-center">
                                <input type="checkbox" id="mail_queue_enabled" name="mail_queue_enabled" <?= $config['mail_queue_enabled'] ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="mail_queue_enabled" class="ml-2 block text-sm text-gray-900">Enable Mail Queue</label>
                            </div>
                            
                            <!-- Queue Connection -->
                            <div>
                                <label for="mail_queue_connection" class="block text-sm font-medium text-gray-700">Queue Connection</label>
                                <input type="text" id="mail_queue_connection" name="mail_queue_connection" value="<?= htmlspecialchars($config['mail_queue_connection'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="redis://127.0.0.1:6379">
                            </div>
                            
                            <!-- Queue Table -->
                            <div>
                                <label for="mail_queue_table" class="block text-sm font-medium text-gray-700">Queue Table</label>
                                <input type="text" id="mail_queue_table" name="mail_queue_table" value="<?= htmlspecialchars($config['mail_queue_table'] ?? 'mail_jobs') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="mail_jobs">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test Email -->
                    <div class="bg-gray-50 px-4 py-5 rounded-lg">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Test Email</h2>
                        
                        <div class="space-y-4">
                            <!-- Test Email Address -->
                            <div>
                                <label for="test_email_address" class="block text-sm font-medium text-gray-700">Test Email Address</label>
                                <input type="email" id="test_email_address" name="test_email_address" value="<?= htmlspecialchars($config['test_email_address'] ?? 'test@example.com') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="test@example.com">
                            </div>
                            
                            <!-- Send Test Email -->
                            <div class="flex items-center">
                                <input type="checkbox" id="send_test_email" name="send_test_email" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="send_test_email" class="ml-2 block text-sm text-gray-900">Send Test Email</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i>
                            Save Configuration
                        </button>
                        
                        <button type="submit" name="send_test_email" value="1" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send Test Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus on first field
document.addEventListener('DOMContentLoaded', function() {
    const mailHostField = document.getElementById('mail_host');
    if (mailHostField) {
        mailHostField.focus();
    }
    
    // Clear form on submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/../components/layout.php';
