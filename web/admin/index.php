<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Cache;
use IMEIBot\Core\Logger;
use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;
use IMEIBot\Models\User;

// Initialize services
$db = Database::getInstance(
    require __DIR__ . '/../../config/database.php',
    new Logger(require __DIR__ . '/../../config/monitoring.php')
);
$cache = new Cache(require __DIR__ . '/../../config/cache.php', new Logger(require __DIR__ . '/../../config/monitoring.php'));
$logger = new Logger(require __DIR__ . '/../../config/monitoring.php');
$imeiGenerator = new IMEIGenerator($db, $cache, $logger);
$fileManager = new FileManager($db, $cache, $logger);
$searchService = new SearchService($db, $cache, $logger);

// Check authentication (simplified for demo)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get dashboard statistics
$stats = [
    'total_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"),
    'active_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL"),
    'premium_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'premium' AND deleted_at IS NULL"),
    'total_files' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL"),
    'total_downloads' => (int) $db->fetchColumn("SELECT COALESCE(SUM(download_count), 0) FROM files WHERE deleted_at IS NULL"),
    'total_imei_generated' => (int) $db->fetchColumn("SELECT COUNT(*) FROM imei_logs"),
    'today_imei_generated' => (int) $db->fetchColumn("SELECT COUNT(*) FROM imei_logs WHERE DATE(created_at) = CURDATE()"),
    'today_downloads' => (int) $db->fetchColumn("SELECT COUNT(*) FROM downloads WHERE DATE(created_at) = CURDATE()"),
];

// Get recent activity
$recent_users = $db->fetchAll("SELECT id, username, first_name, last_name, role, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
$recent_files = $db->fetchAll("SELECT id, name, display_name, category, file_size_mb, download_count, created_at FROM files WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
$recent_imei_logs = $db->fetchAll("SELECT id, imei, prefix, type, platform, created_at FROM imei_logs ORDER BY created_at DESC LIMIT 5");

// Get system health checks
$health_checks = [
    'database' => $db->healthCheck(),
    'cache' => $cache->healthCheck(),
    'logger' => $logger->healthCheck(),
];

// Get file statistics by category
$category_stats = $db->fetchAll("
    SELECT category, COUNT(*) as count, SUM(download_count) as downloads
    FROM files
    WHERE deleted_at IS NULL
    GROUP BY category
    ORDER BY count DESC
");

// Get user statistics by role
$role_stats = $db->fetchAll("
    SELECT role, COUNT(*) as count
    FROM users
    WHERE deleted_at IS NULL
    GROUP BY role
    ORDER BY count DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IMEI Bot Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-900">Dashboard</a>
                    <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Users</a>
                    <a href="files.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Files</a>
                    <a href="analytics.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Analytics</a>
                    <a href="settings.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Settings</a>
                    <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600">Welcome to the IMEI Bot Platform admin dashboard</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-500 rounded-full">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-500 rounded-full">
                        <i class="fas fa-file text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Files</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_files']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-500 rounded-full">
                        <i class="fas fa-download text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Downloads</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_downloads']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-500 rounded-full">
                        <i class="fas fa-mobile-alt text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">IMEI Generated</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_imei_generated']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- User Growth Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">User Growth (Last 7 Days)</h3>
                <canvas id="userGrowthChart" height="200"></canvas>
            </div>

            <!-- File Categories Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Files by Category</h3>
                <canvas id="categoryChart" height="200"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Recent Users -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Recent Users</h3>
                <div class="space-y-3">
                    <?php foreach ($recent_users as $user): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['username'] ?? 'No username'); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs px-2 py-1 bg-<?php echo $user['role'] === 'premium' ? 'gold' : 'gray'; ?>-100 rounded-full">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                                <p class="text-xs text-gray-500"><?php echo date('M j', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Files -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Recent Files</h3>
                <div class="space-y-3">
                    <?php foreach ($recent_files as $file): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($file['display_name'] ?: $file['name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo ucfirst($file['category']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium"><?php echo number_format((int) $file['download_count']); ?> downloads</p>
                                <p class="text-xs text-gray-500"><?php echo number_format((float) $file['file_size_mb'], 1); ?> MB</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent IMEI Logs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Recent IMEI Generation</h3>
                <div class="space-y-3">
                    <?php foreach ($recent_imei_logs as $log): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($log['imei']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo ucfirst($log['type']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium"><?php echo ucfirst($log['platform']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('H:i', strtotime($log['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">System Health</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($health_checks as $service => $check): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium capitalize"><?php echo $service; ?></span>
                            <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $check['status'] === 'healthy' ? 'green' : 'red'; ?>-100 text-<?php echo $check['status'] === 'healthy' ? 'green' : 'red'; ?>-800">
                                <?php echo ucfirst($check['status']); ?>
                            </span>
                        </div>
                        <?php if ($check['status'] === 'healthy'): ?>
                            <p class="text-sm text-gray-600">Response time: <?php echo round($check['response_time_ms'] ?? 0, 2); ?>ms</p>
                        <?php else: ?>
                            <p class="text-sm text-red-600"><?php echo htmlspecialchars($check['error'] ?? 'Unknown error'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: ['7 days ago', '6 days ago', '5 days ago', '4 days ago', '3 days ago', '2 days ago', 'Yesterday', 'Today'],
                datasets: [{
                    label: 'New Users',
                    data: [12, 19, 15, 25, 22, 30, 28, 35],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($category_stats, 'category')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($category_stats, 'count')); ?>,
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(251, 146, 60)',
                        'rgb(147, 51, 234)',
                        'rgb(244, 63, 94)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>
