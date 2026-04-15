<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

session_start();

// Initialize services
$logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/admin.log']]);
$db = Database::getInstance(
    ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
    $logger
);
$auth = new AuthMiddleware($db, $logger);

// Require admin authentication
$auth->requireAdmin('/dashboard');

// Layout configuration
$title = 'Admin Dashboard';
$description = 'System overview and management controls';
$showSidebar = true;
$isAdminPanel = true;
$pageHeader = [
    'title' => 'Admin Dashboard',
    'description' => 'System overview and management controls.'
];
$breadcrumbs = [
    ['name' => 'Dashboard', 'url' => '/admin/dashboard.php']
];

// Get admin statistics
$stats = [
    'total_users' => 0,
    'users_by_role' => [],
    'users_by_status' => [],
    'registrations_24h' => 0,
    'active_users_24h' => 0,
    'premium_users' => 0
];

// Get statistics from database
try {
    $db = \IMEIBot\Core\Database::getInstance(
        ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
        new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/app.log']])
    );

    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
    $stats['total_users'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['total'];

    // Users by role
    $stmt = $db->query("SELECT role, COUNT(*) as count FROM users WHERE deleted_at IS NULL GROUP BY role");
    $stats['users_by_role'] = [];
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $stats['users_by_role'][$row['role']] = (int) $row['count'];
    }

    // Users by status
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM users WHERE deleted_at IS NULL GROUP BY status");
    $stats['users_by_status'] = [];
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $stats['users_by_status'][$row['status']] = (int) $row['count'];
    }

    // Recent registrations
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND deleted_at IS NULL");
    $stats['registrations_24h'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

    // Active users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE last_activity_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND deleted_at IS NULL");
    $stats['active_users_24h'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

    // Premium users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'premium' AND premium_expires_at > NOW() AND deleted_at IS NULL");
    $stats['premium_users'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

} catch (\Exception $e) {
    $error = 'Failed to load statistics';
}

// Start content buffer
ob_start();
?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Total Users
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format($stats['total_users']); ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-plus text-green-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            New Users (24h)
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format($stats['registrations_24h']); ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Active Users (24h)
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format($stats['active_users_24h']); ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-crown text-yellow-600 text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Premium Users
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            <?php echo number_format($stats['premium_users']); ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Users by Role Chart -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Users by Role</h3>
            <canvas id="roleChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Users by Status Chart -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Users by Status</h3>
            <canvas id="statusChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white shadow rounded-lg mb-8">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="/admin/users.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-users mr-2"></i>
                Manage Users
            </a>
            <a href="/admin/analytics.php" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-chart-bar mr-2"></i>
                View Analytics
            </a>
            <a href="/admin/logs.php" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-file-alt mr-2"></i>
                System Logs
            </a>
            <?php if ($user['role'] === 'super_admin'): ?>
                <a href="/admin/settings.php" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-cogs mr-2"></i>
                    Settings
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">System Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                    <i class="fas fa-check-circle mr-2"></i>Online
                </div>
                <p class="text-sm text-gray-500 mt-1">System Status</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">
                    <?php echo date('H:i:s'); ?>
                </div>
                <p class="text-sm text-gray-500 mt-1">Server Time</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">
                    <?php echo $stats['active_users_24h']; ?>
                </div>
                <p class="text-sm text-gray-500 mt-1">Active Now</p>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Users by Role Chart
    const roleCtx = document.getElementById('roleChart').getContext('2d');
    new Chart(roleCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats['users_by_role'])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats['users_by_role'])); ?>,
                backgroundColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Users by Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($stats['users_by_status'])); ?>,
            datasets: [{
                label: 'Users',
                data: <?php echo json_encode(array_values($stats['users_by_status'])); ?>,
                backgroundColor: '#3B82F6'
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
});

// Auto-refresh dashboard data every 60 seconds
setInterval(() => {
    location.reload();
}, 60000);
</script>

<?php
// Capture content
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/../components/layout.php';
?>
