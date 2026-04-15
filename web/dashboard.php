<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

session_start();

// Initialize services
$logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/user.log']]);
$db = Database::getInstance(
    ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
    $logger
);
$auth = new AuthMiddleware($db, $logger);

// Require authentication and active status
$auth->requireAuth('/auth/login');
$auth->requireActive('/auth/login');

// Get current user
$user = $auth->getCurrentUser();

// Layout configuration
$title = 'Dashboard';
$description = 'Your IMEI Bot Platform dashboard';
$showSidebar = true;
$isAdminPanel = false;
$pageHeader = [
    'title' => 'Welcome back, ' . htmlspecialchars($user['first_name'] ?: $user['username'] ?: 'User') . '!',
    'description' => 'Here\'s an overview of your IMEI Bot Platform activity.'
];
$breadcrumbs = [
    ['name' => 'Dashboard', 'url' => '/dashboard.php']
];

// Get user statistics
$stats = [
    'total_imei_generated' => $user['total_imei_generated'],
    'total_files_downloaded' => $user['total_files_downloaded'],
    'daily_imei_limit' => $user['daily_imei_limit'],
    'daily_download_limit' => $user['daily_download_limit'],
    'storage_quota_mb' => $user['storage_quota_mb'],
    'storage_used_mb' => $user['storage_used_mb'],
    'referral_count' => $user['referral_count'],
    'is_premium' => $user['role'] === 'premium' && ($user['premium_expires_at'] === null || new DateTime($user['premium_expires_at']) > new DateTime())
];

// Calculate usage percentages
$usage = [
    'imei_percentage' => min(100, round(($stats['total_imei_generated'] / max(1, $stats['daily_imei_limit'])) * 100)),
    'download_percentage' => min(100, round(($stats['total_files_downloaded'] / max(1, $stats['daily_download_limit'])) * 100)),
    'storage_percentage' => min(100, round(($stats['storage_used_mb'] / max(1, $stats['storage_quota_mb'])) * 100))
];

// Start content buffer
ob_start();
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-mobile-alt text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            IMEI Generated
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format((int) $stats['total_imei_generated']); ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Daily Limit</span>
                    <span class="font-medium"><?php echo $stats['daily_imei_limit'] === 999999 ? 'Unlimited' : number_format((int) $stats['daily_imei_limit']); ?></span>
                </div>
                <div class="mt-1">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $usage['imei_percentage']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-download text-green-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Files Downloaded
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format((int) $stats['total_files_downloaded']); ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Daily Limit</span>
                    <span class="font-medium"><?php echo $stats['daily_download_limit'] === 999999 ? 'Unlimited' : number_format((int) $stats['daily_download_limit']); ?></span>
                </div>
                <div class="mt-1">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $usage['download_percentage']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-hdd text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Storage Used
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format((float) $stats['storage_used_mb']); ?> MB
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Quota</span>
                    <span class="font-medium"><?php echo number_format((float) $stats['storage_quota_mb']); ?> MB</span>
                </div>
                <div class="mt-1">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $usage['storage_percentage']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-orange-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Referrals
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format((int) $stats['referral_count']); ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <span class="font-medium text-gray-900">Your Code:</span>
                <code class="ml-2 bg-gray-200 px-2 py-1 rounded"><?php echo htmlspecialchars($user['referral_code'] ?? 'N/A'); ?></code>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white shadow rounded-lg mb-8">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="/imei-generator.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-mobile-alt mr-2"></i>
                Generate IMEI
            </a>
            <a href="/files.php" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-download mr-2"></i>
                Browse Files
            </a>
            <a href="/bot-setup.php" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-robot mr-2"></i>
                Bot Setup
            </a>
            <?php if (!$stats['is_premium']): ?>
                <a href="/upgrade.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                    <i class="fas fa-crown mr-2"></i>
                    Upgrade
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activity</h3>
        <div class="flow-root">
            <ul class="-mb-8">
                <li>
                    <div class="relative pb-8">
                        <div class="relative flex space-x-3">
                            <div class="flex-shrink-0">
                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                    <i class="fas fa-sign-in-alt text-white text-xs"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">Logged in to your account</p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    Just now
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php if ($user['last_login_at']): ?>
                    <li>
                        <div class="relative pb-8">
                            <div class="relative flex space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                        <i class="fas fa-history text-white text-xs"></i>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Previous login</p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                        <?php echo date('M j, Y', strtotime($user['last_login_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php
// Capture content
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/components/layout.php';
?>
