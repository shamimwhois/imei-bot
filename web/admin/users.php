<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;

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

// Handle user actions
$action = $_GET['action'] ?? '';
$userId = $_GET['id'] ?? 0;

if ($action === 'delete' && $userId > 0) {
    $user = User::find($userId);
    if ($user) {
        $user->delete();
        $_SESSION['success'] = 'User deleted successfully';
    }
    header('Location: users.php');
    exit;
}

if ($action === 'toggle_premium' && $userId > 0) {
    $user = User::find($userId);
    if ($user) {
        if ($user->isPremium()) {
            $user->downgradeToFree();
            $_SESSION['success'] = 'User downgraded to free plan';
        } else {
            $expiresAt = new \DateTime('+30 days');
            $user->upgradeToPremium($expiresAt);
            $_SESSION['success'] = 'User upgraded to premium plan';
        }
    }
    header('Location: users.php');
    exit;
}

if ($action === 'toggle_status' && $userId > 0) {
    $user = User::find($userId);
    if ($user) {
        $newStatus = $user->getStatus() === 'active' ? 'suspended' : 'active';
        $user->status = $newStatus;
        $user->save();
        $_SESSION['success'] = 'User status updated successfully';
    }
    header('Location: users.php');
    exit;
}

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Search and filters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = ['deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(username LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($role) {
    $where[] = "role = :role";
    $params[':role'] = $role;
}

if ($status) {
    $where[] = "status = :status";
    $params[':status'] = $status;
}

$whereClause = implode(' AND ', $where);

// Get users
$sql = "SELECT * FROM users WHERE {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM users WHERE {$whereClause}";
$countStmt = $db->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalUsers = (int) $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Get statistics
$stats = [
    'total' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"),
    'active' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL"),
    'premium' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'premium' AND deleted_at IS NULL"),
    'suspended' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'suspended' AND deleted_at IS NULL"),
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - IMEI Bot Platform</title>
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
                    <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-900">Users</a>
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
            <h1 class="text-3xl font-bold text-gray-900">Users Management</h1>
            <p class="text-gray-600">Manage platform users and their accounts</p>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-500 rounded-full">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total']); ?></p>
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
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-500 rounded-full">
                        <i class="fas fa-crown text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Premium Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['premium']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-500 rounded-full">
                        <i class="fas fa-ban text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Suspended Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['suspended']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <input type="text" name="search" placeholder="Search users..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="premium" <?php echo $role === 'premium' ? 'selected' : ''; ?>>Premium</option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Users (<?php echo number_format($totalUsers); ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IMEI Generated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files Downloaded</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['username'] ?? 'No username'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo match($user['role']) {
                                            'admin' => 'bg-purple-100 text-purple-800',
                                            'premium' => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        }; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo match($user['status']) {
                                            'active' => 'bg-green-100 text-green-800',
                                            'suspended' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        }; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format((int) $user['total_imei_generated']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format((int) $user['total_files_downloaded']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="user-details.php?id=<?php echo $user['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=toggle_premium&id=<?php echo $user['id']; ?>" 
                                           class="text-yellow-600 hover:text-yellow-900"
                                           onclick="return confirm('Are you sure you want to toggle premium status?')">
                                            <i class="fas fa-crown"></i>
                                        </a>
                                        <a href="?action=toggle_status&id=<?php echo $user['id']; ?>" 
                                           class="text-green-600 hover:text-green-900"
                                           onclick="return confirm('Are you sure you want to toggle user status?')">
                                            <i class="fas fa-toggle-on"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                           class="text-red-600 hover:text-red-900"
                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalUsers); ?> of <?php echo $totalUsers; ?> results
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 border <?php echo $i === $page ? 'bg-blue-500 text-white' : 'border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
