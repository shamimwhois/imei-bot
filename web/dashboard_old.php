<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Services\JWTService;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get user data
$user = User::findByIdAsArray($_SESSION['user_id']);
if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: /auth/login');
    exit;
}

// Update last activity
User::updateLastActivity($user['id']);

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-robot text-blue-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">IMEI Bot Platform</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/dashboard" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="/imei-generator" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            IMEI Generator
                        </a>
                        <a href="/files" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            File Downloads
                        </a>
                        <a href="/analytics" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Analytics
                        </a>
                        <?php if ($user['role'] === 'admin' || $user['role'] === 'super_admin'): ?>
                            <a href="/admin" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                <i class="fas fa-cog mr-1"></i>
                                Admin
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                                <span class="text-white font-medium">
                                    <?php echo strtoupper(substr($user['first_name'] ?: $user['username'] ?: $user['email'], 0, 1)); ?>
                                </span>
                            </div>
                            <span class="ml-2 text-gray-700 font-medium"><?php echo htmlspecialchars($user['first_name'] ?: $user['username'] ?: $user['email']); ?></span>
                            <?php if ($stats['is_premium']): ?>
                                <i class="fas fa-crown text-yellow-500 ml-2"></i>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down text-gray-400 ml-2"></i>
                        </button>
                        
                        <!-- User Menu Dropdown -->
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i> Profile
                                </a>
                                <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i> Settings
                                </a>
                                <?php if (!$stats['is_premium']): ?>
                                    <a href="/upgrade" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-crown mr-2"></i> Upgrade to Premium
                                    </a>
                                <?php endif; ?>
                                <hr class="my-1">
                                <a href="/auth/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- Welcome Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    Welcome back, <?php echo htmlspecialchars($user['first_name'] ?: $user['username'] ?: 'User'); ?>!
                </h1>
                <p class="mt-2 text-gray-600">
                    Here's an overview of your IMEI Bot Platform activity.
                </p>
            </div>

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
                                        <?php echo number_format($stats['total_imei_generated']); ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Daily Limit</span>
                                <span class="font-medium"><?php echo $stats['daily_imei_limit'] === 999999 ? 'Unlimited' : number_format($stats['daily_imei_limit']); ?></span>
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
                                        <?php echo number_format($stats['total_files_downloaded']); ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Daily Limit</span>
                                <span class="font-medium"><?php echo $stats['daily_download_limit'] === 999999 ? 'Unlimited' : number_format($stats['daily_download_limit']); ?></span>
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
                                        <?php echo number_format($stats['storage_used_mb']); ?> MB
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Quota</span>
                                <span class="font-medium"><?php echo number_format($stats['storage_quota_mb']); ?> MB</span>
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
                                        <?php echo number_format($stats['referral_count']); ?>
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
                        <a href="/imei-generator" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-mobile-alt mr-2"></i>
                            Generate IMEI
                        </a>
                        <a href="/files" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-download mr-2"></i>
                            Browse Files
                        </a>
                        <a href="/bot-setup" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-robot mr-2"></i>
                            Bot Setup
                        </a>
                        <?php if (!$stats['is_premium']): ?>
                            <a href="/upgrade" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
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
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleUserMenu') === -1) {
                menu.classList.add('hidden');
            }
        });

        // Auto-refresh dashboard data every 30 seconds
        setInterval(() => {
            // You can implement AJAX refresh here if needed
            console.log('Dashboard data refreshed');
        }, 30000);
    </script>
</body>
</html>
