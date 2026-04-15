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

// Get date range
$dateRange = $_GET['date_range'] ?? '7days';
switch ($dateRange) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case '7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        break;
    case '90days':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $endDate = date('Y-m-d');
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
}

// Get overview statistics
$overview = [
    'total_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"),
    'new_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN :start AND :end AND deleted_at IS NULL", [':start' => $startDate, ':end' => $endDate]),
    'active_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL"),
    'premium_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'premium' AND deleted_at IS NULL"),
    'total_files' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL"),
    'total_downloads' => (int) $db->fetchColumn("SELECT COALESCE(SUM(download_count), 0) FROM files WHERE deleted_at IS NULL"),
    'total_imei_generated' => (int) $db->fetchColumn("SELECT COUNT(*) FROM imei_logs"),
    'imei_generated_period' => (int) $db->fetchColumn("SELECT COUNT(*) FROM imei_logs WHERE DATE(created_at) BETWEEN :start AND :end", [':start' => $startDate, ':end' => $endDate]),
];

// Get user growth data
$userGrowth = $db->fetchAll("
    SELECT DATE(created_at) as date, COUNT(*) as users
    FROM users
    WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

// Get IMEI generation trends
$imeiTrends = $db->fetchAll("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM imei_logs
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

// Get download trends
$downloadTrends = $db->fetchAll("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM downloads
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

// Get platform distribution
$platformDistribution = $db->fetchAll("
    SELECT platform, COUNT(*) as count
    FROM imei_logs
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY platform
    ORDER BY count DESC
");

// Get file category distribution
$categoryDistribution = $db->fetchAll("
    SELECT category, COUNT(*) as count, SUM(download_count) as downloads
    FROM files
    WHERE deleted_at IS NULL
    GROUP BY category
    ORDER BY count DESC
");

// Get top brands
$topBrands = $db->fetchAll("
    SELECT b.name, COUNT(f.id) as file_count, SUM(f.download_count) as total_downloads
    FROM brands b
    LEFT JOIN files f ON b.id = f.brand_id AND f.deleted_at IS NULL
    WHERE b.deleted_at IS NULL
    GROUP BY b.id, b.name
    HAVING file_count > 0
    ORDER BY file_count DESC
    LIMIT 10
");

// Get top users
$topUsers = $db->fetchAll("
    SELECT u.username, u.first_name, u.last_name, u.total_imei_generated, u.total_files_downloaded
    FROM users u
    WHERE u.deleted_at IS NULL
    ORDER BY u.total_imei_generated DESC
    LIMIT 10
");

// Get recent activity
$recentActivity = $db->fetchAll("
    (SELECT 'user' as type, CONCAT('New user: ', COALESCE(username, first_name, 'Unknown')) as description, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'file' as type, CONCAT('New file: ', display_name) as description, created_at FROM files WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'imei' as type, CONCAT('IMEI generated: ', imei) as description, created_at FROM imei_logs ORDER BY created_at DESC LIMIT 5)
    ORDER BY created_at DESC
    LIMIT 10
");

// Get hourly activity for today
$hourlyActivity = $db->fetchAll("
    SELECT 
        HOUR(created_at) as hour,
        SUM(CASE WHEN platform = 'telegram' THEN 1 ELSE 0 END) as telegram,
        SUM(CASE WHEN platform = 'whatsapp' THEN 1 ELSE 0 END) as whatsapp,
        SUM(CASE WHEN platform = 'web' THEN 1 ELSE 0 END) as web
    FROM imei_logs
    WHERE DATE(created_at) = CURDATE()
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - IMEI Bot Platform</title>
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
                    <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Dashboard</a>
                    <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Users</a>
                    <a href="files.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Files</a>
                    <a href="analytics.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-900">Analytics</a>
                    <a href="settings.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Settings</a>
                    <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Analytics</h1>
                <p class="text-gray-600">Platform usage statistics and insights</p>
            </div>
            <form method="GET" class="flex space-x-2">
                <select name="date_range" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="today" <?php echo $dateRange === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="7days" <?php echo $dateRange === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="30days" <?php echo $dateRange === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90days" <?php echo $dateRange === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Apply
                </button>
            </form>
        </div>

        <!-- Overview Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-500 rounded-full">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($overview['total_users']); ?></p>
                        <p class="text-xs text-green-600">+<?php echo $overview['new_users']; ?> new</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-500 rounded-full">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($overview['active_users']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo round(($overview['active_users'] / $overview['total_users']) * 100, 1); ?>% active</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-500 rounded-full">
                        <i class="fas fa-crown text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Premium Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($overview['premium_users']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo round(($overview['premium_users'] / $overview['total_users']) * 100, 1); ?>% premium</p>
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
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($overview['imei_generated_period']); ?></p>
                        <p class="text-xs text-gray-500">in selected period</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- User Growth Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">User Growth (Last 30 Days)</h3>
                <canvas id="userGrowthChart" height="200"></canvas>
            </div>

            <!-- Platform Distribution -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Platform Distribution</h3>
                <canvas id="platformChart" height="200"></canvas>
            </div>
        </div>

        <!-- Activity Trends -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- IMEI Generation Trends -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">IMEI Generation Trends</h3>
                <canvas id="imeiTrendsChart" height="200"></canvas>
            </div>

            <!-- Download Trends -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Download Trends</h3>
                <canvas id="downloadTrendsChart" height="200"></canvas>
            </div>
        </div>

        <!-- Detailed Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Top Brands -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Top Brands by Files</h3>
                <div class="space-y-3">
                    <?php foreach ($topBrands as $brand): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($brand['name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo number_format((int) $brand['file_count']); ?> files</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium"><?php echo number_format((int) $brand['total_downloads']); ?></p>
                                <p class="text-xs text-gray-500">downloads</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- File Categories -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">File Categories</h3>
                <div class="space-y-3">
                    <?php foreach ($categoryDistribution as $category): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium"><?php echo ucfirst($category['category']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo number_format((int) $category['count']); ?> files</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium"><?php echo number_format((int) $category['downloads']); ?></p>
                                <p class="text-xs text-gray-500">downloads</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Users -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Top Users by Activity</h3>
                <div class="space-y-3">
                    <?php foreach ($topUsers as $user): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['username'] ?? 'No username'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium"><?php echo number_format((int) $user['total_imei_generated']); ?></p>
                                <p class="text-xs text-gray-500">IMEI generated</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Hourly Activity -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold mb-4">Today's Hourly Activity</h3>
            <canvas id="hourlyActivityChart" height="150"></canvas>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
            <div class="space-y-3">
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-<?php echo match($activity['type']) {
                                'user' => 'blue',
                                'file' => 'green',
                                'imei' => 'orange',
                                default => 'gray'
                            }; ?>-100 rounded-full mr-3">
                                <i class="fas fa-<?php echo match($activity['type']) {
                                    'user' => 'user',
                                    'file' => 'file',
                                    'imei' => 'mobile-alt',
                                    default => 'circle'
                                }; ?> text-<?php echo match($activity['type']) {
                                    'user' => 'blue',
                                    'file' => 'green',
                                    'imei' => 'orange',
                                    default => 'gray'
                                }; ?>"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></p>
                            </div>
                        </div>
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
                labels: <?php echo json_encode(array_column($userGrowth, 'date')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($userGrowth, 'users')); ?>,
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

        // Platform Distribution Chart
        const platformCtx = document.getElementById('platformChart').getContext('2d');
        new Chart(platformCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($platformDistribution, 'platform')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($platformDistribution, 'count')); ?>,
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(251, 146, 60)',
                        'rgb(147, 51, 234)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // IMEI Trends Chart
        const imeiTrendsCtx = document.getElementById('imeiTrendsChart').getContext('2d');
        new Chart(imeiTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($imeiTrends, 'date')); ?>,
                datasets: [{
                    label: 'IMEI Generated',
                    data: <?php echo json_encode(array_column($imeiTrends, 'count')); ?>,
                    borderColor: 'rgb(251, 146, 60)',
                    backgroundColor: 'rgba(251, 146, 60, 0.1)',
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

        // Download Trends Chart
        const downloadTrendsCtx = document.getElementById('downloadTrendsChart').getContext('2d');
        new Chart(downloadTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($downloadTrends, 'date')); ?>,
                datasets: [{
                    label: 'Downloads',
                    data: <?php echo json_encode(array_column($downloadTrends, 'count')); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
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

        // Hourly Activity Chart
        const hourlyActivityCtx = document.getElementById('hourlyActivityChart').getContext('2d');
        new Chart(hourlyActivityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(fn($h) => $h . ':00', range(0, 23))); ?>,
                datasets: [
                    {
                        label: 'Telegram',
                        data: <?php echo json_encode(array_column($hourlyActivity, 'telegram')); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)'
                    },
                    {
                        label: 'WhatsApp',
                        data: <?php echo json_encode(array_column($hourlyActivity, 'whatsapp')); ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.8)'
                    },
                    {
                        label: 'Web',
                        data: <?php echo json_encode(array_column($hourlyActivity, 'web')); ?>,
                        backgroundColor: 'rgba(251, 146, 60, 0.8)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true
                    },
                    x: {
                        stacked: true
                    }
                }
            }
        });
    </script>
</body>
</html>
