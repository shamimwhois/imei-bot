<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

// Initialize services
$db = Database::getInstance(
    require __DIR__ . '/../../config/database.php',
    new Logger(require __DIR__ . '/../../config/monitoring.php')
);
$logger = new Logger(require __DIR__ . '/../../config/monitoring.php');

// Check authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = $_POST['settings'] ?? [];
    
    foreach ($settings as $key => $value) {
        $sql = "INSERT INTO settings (key, value, type, group, description, created_at, updated_at) 
                VALUES (:key, :value, :type, :group, :description, NOW(), NOW())
                ON DUPLICATE KEY UPDATE value = :value, updated_at = NOW()";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':key' => $key,
            ':value' => is_array($value) ? json_encode($value) : $value,
            ':type' => is_array($value) ? 'json' : 'string',
            ':group' => $_POST['group'] ?? 'general',
            ':description' => 'Updated via admin panel'
        ]);
    }
    
    $_SESSION['success'] = 'Settings updated successfully';
    header('Location: settings.php');
    exit;
}

// Get current settings
$currentSettings = [];
$settingsData = $db->fetchAll("SELECT * FROM settings ORDER BY `group`, `key`");
foreach ($settingsData as $setting) {
    $currentSettings[$setting['group']][$setting['key']] = $setting['type'] === 'json' ? json_decode($setting['value'], true) : $setting['value'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - IMEI Bot Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-robot text-2xl mr-3"></i>
                    <span class="font-bold text-xl">IMEI Bot Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Dashboard</a>
                    <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Users</a>
                    <a href="files.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Files</a>
                    <a href="analytics.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Analytics</a>
                    <a href="settings.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-900">Settings</a>
                    <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
            <p class="text-gray-600">Configure platform settings and preferences</p>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <div class="bg-white rounded-lg shadow">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex">
                    <button onclick="showTab('general')" class="tab-btn active py-2 px-4 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                        General
                    </button>
                    <button onclick="showTab('telegram')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Telegram Bot
                    </button>
                    <button onclick="showTab('whatsapp')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        WhatsApp Bot
                    </button>
                    <button onclick="showTab('limits')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Rate Limits
                    </button>
                    <button onclick="showTab('notifications')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Notifications
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- General Settings -->
                <div id="general-tab" class="tab-content">
                    <form method="POST">
                        <input type="hidden" name="group" value="general">
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Application Settings</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Application Name</label>
                                        <input type="text" name="settings[app_name]" 
                                               value="<?php echo htmlspecialchars($currentSettings['general']['app_name'] ?? 'IMEI Bot Platform'); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Application URL</label>
                                        <input type="url" name="settings[app_url]" 
                                               value="<?php echo htmlspecialchars($currentSettings['general']['app_url'] ?? 'https://your-domain.com'); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Default Language</label>
                                        <select name="settings[default_language]" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="en" <?php echo ($currentSettings['general']['default_language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                            <option value="es" <?php echo ($currentSettings['general']['default_language'] ?? 'en') === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                            <option value="fr" <?php echo ($currentSettings['general']['default_language'] ?? 'en') === 'fr' ? 'selected' : ''; ?>>French</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Default Timezone</label>
                                        <select name="settings[default_timezone]" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="UTC" <?php echo ($currentSettings['general']['default_timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo ($currentSettings['general']['default_timezone'] ?? 'UTC') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                            <option value="Europe/London" <?php echo ($currentSettings['general']['default_timezone'] ?? 'UTC') === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">IMEI Generation Settings</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Supported Prefixes</label>
                                        <input type="text" name="settings[imei_prefixes]" 
                                               value="<?php echo htmlspecialchars(implode(',', $currentSettings['general']['imei_prefixes'] ?? ['35', '86', '89', '45'])); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="35,86,89,45">
                                        <p class="mt-1 text-sm text-gray-500">Comma-separated list of supported IMEI prefixes</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Max IMEI per Request</label>
                                        <input type="number" name="settings[max_imei_per_request]" 
                                               value="<?php echo htmlspecialchars($currentSettings['general']['max_imei_per_request'] ?? 50); ?>"
                                               min="1" max="100"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save General Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Telegram Bot Settings -->
                <div id="telegram-tab" class="tab-content hidden">
                    <form method="POST">
                        <input type="hidden" name="group" value="telegram">
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Bot Configuration</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Bot Token</label>
                                        <input type="text" name="settings[telegram_bot_token]" 
                                               value="<?php echo htmlspecialchars($currentSettings['telegram']['telegram_bot_token'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Get this from @BotFather on Telegram</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Webhook URL</label>
                                        <input type="url" name="settings[telegram_webhook_url]" 
                                               value="<?php echo htmlspecialchars($currentSettings['telegram']['telegram_webhook_url'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Admin Chat ID</label>
                                        <input type="text" name="settings[telegram_admin_chat_id]" 
                                               value="<?php echo htmlspecialchars($currentSettings['telegram']['telegram_admin_chat_id'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Chat ID for admin notifications</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Enable Bot</label>
                                        <select name="settings[telegram_enabled]" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="1" <?php echo ($currentSettings['telegram']['telegram_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                            <option value="0" <?php echo ($currentSettings['telegram']['telegram_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Rate Limits</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Messages per Minute</label>
                                        <input type="number" name="settings[telegram_rate_limit_minute]" 
                                               value="<?php echo htmlspecialchars($currentSettings['telegram']['telegram_rate_limit_minute'] ?? 30); ?>"
                                               min="1" max="100"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Messages per Hour</label>
                                        <input type="number" name="settings[telegram_rate_limit_hour]" 
                                               value="<?php echo htmlspecialchars($currentSettings['telegram']['telegram_rate_limit_hour'] ?? 1000); ?>"
                                               min="1" max="10000"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save Telegram Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- WhatsApp Bot Settings -->
                <div id="whatsapp-tab" class="tab-content hidden">
                    <form method="POST">
                        <input type="hidden" name="group" value="whatsapp">
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Bot Configuration</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Access Token</label>
                                        <input type="text" name="settings[whatsapp_access_token]" 
                                               value="<?php echo htmlspecialchars($currentSettings['whatsapp']['whatsapp_access_token'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Phone Number ID</label>
                                        <input type="text" name="settings[whatsapp_phone_number_id]" 
                                               value="<?php echo htmlspecialchars($currentSettings['whatsapp']['whatsapp_phone_number_id'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Webhook URL</label>
                                        <input type="url" name="settings[whatsapp_webhook_url]" 
                                               value="<?php echo htmlspecialchars($currentSettings['whatsapp']['whatsapp_webhook_url'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Webhook Verify Token</label>
                                        <input type="text" name="settings[whatsapp_webhook_verify_token]" 
                                               value="<?php echo htmlspecialchars($currentSettings['whatsapp']['whatsapp_webhook_verify_token'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Enable Bot</label>
                                        <select name="settings[whatsapp_enabled]" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="1" <?php echo ($currentSettings['whatsapp']['whatsapp_enabled'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                            <option value="0" <?php echo ($currentSettings['whatsapp']['whatsapp_enabled'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save WhatsApp Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Rate Limits Settings -->
                <div id="limits-tab" class="tab-content hidden">
                    <form method="POST">
                        <input type="hidden" name="group" value="limits">
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">User Rate Limits</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Free User Daily IMEI Limit</label>
                                        <input type="number" name="settings[free_daily_imei_limit]" 
                                               value="<?php echo htmlspecialchars($currentSettings['limits']['free_daily_imei_limit'] ?? 10); ?>"
                                               min="1" max="100"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Free User Daily Download Limit</label>
                                        <input type="number" name="settings[free_daily_download_limit]" 
                                               value="<?php echo htmlspecialchars($currentSettings['limits']['free_daily_download_limit'] ?? 5); ?>"
                                               min="1" max="100"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Premium User Daily IMEI Limit</label>
                                        <input type="number" name="settings[premium_daily_imei_limit]" 
                                               value="<?php echo htmlspecialchars($currentSettings['limits']['premium_daily_imei_limit'] ?? 999999); ?>"
                                               min="1" max="999999"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Premium User Daily Download Limit</label>
                                        <input type="number" name="settings[premium_daily_download_limit]" 
                                               value="<?php echo htmlspecialchars($currentSettings['limits']['premium_daily_download_limit'] ?? 999999); ?>"
                                               min="1" max="999999"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save Rate Limit Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Notifications Settings -->
                <div id="notifications-tab" class="tab-content hidden">
                    <form method="POST">
                        <input type="hidden" name="group" value="notifications">
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Email Notifications</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">SMTP Host</label>
                                        <input type="text" name="settings[smtp_host]" 
                                               value="<?php echo htmlspecialchars($currentSettings['notifications']['smtp_host'] ?? 'smtp.gmail.com'); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">SMTP Port</label>
                                        <input type="number" name="settings[smtp_port]" 
                                               value="<?php echo htmlspecialchars($currentSettings['notifications']['smtp_port'] ?? 587); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">SMTP Username</label>
                                        <input type="email" name="settings[smtp_username]" 
                                               value="<?php echo htmlspecialchars($currentSettings['notifications']['smtp_username'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">SMTP Password</label>
                                        <input type="password" name="settings[smtp_password]" 
                                               value="<?php echo htmlspecialchars($currentSettings['notifications']['smtp_password'] ?? ''); ?>"
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Types</h3>
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="settings[notify_new_user]" value="1" 
                                               <?php echo ($currentSettings['notifications']['notify_new_user'] ?? '1') === '1' ? 'checked' : ''; ?>
                                               class="mr-2">
                                        <span class="text-sm font-medium text-gray-700">Notify on new user registration</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="settings[notify_premium_upgrade]" value="1" 
                                               <?php echo ($currentSettings['notifications']['notify_premium_upgrade'] ?? '1') === '1' ? 'checked' : ''; ?>
                                               class="mr-2">
                                        <span class="text-sm font-medium text-gray-700">Notify on premium upgrades</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="settings[notify_system_errors]" value="1" 
                                               <?php echo ($currentSettings['notifications']['notify_system_errors'] ?? '1') === '1' ? 'checked' : ''; ?>
                                               class="mr-2">
                                        <span class="text-sm font-medium text-gray-700">Notify on system errors</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save Notification Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showTab(tabName) {
            // Hide all tab content
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600', 'active');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.style.display = 'block';
            }
            
            // Add active class to selected tab button
            const selectedBtn = document.querySelector(`[onclick="showTab('${tabName}')"]`);
            if (selectedBtn) {
                selectedBtn.classList.remove('border-transparent', 'text-gray-500');
                selectedBtn.classList.add('border-blue-500', 'text-blue-600', 'active');
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Hide all tabs initially
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Show only the general tab by default
            const generalTab = document.getElementById('general-tab');
            if (generalTab) {
                generalTab.style.display = 'block';
            }
            
            // Set general tab button as active
            const generalBtn = document.querySelector('[onclick="showTab(\'general\')"]');
            if (generalBtn) {
                generalBtn.classList.add('border-blue-500', 'text-blue-600', 'active');
                generalBtn.classList.remove('border-transparent', 'text-gray-500');
            }
        });
    </script>
</body>
</html>
