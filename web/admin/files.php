<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\File;
use IMEIBot\Models\Brand;
use IMEIBot\Models\Model;

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

// Handle file actions
$action = $_GET['action'] ?? '';
$fileId = $_GET['id'] ?? 0;

if ($action === 'delete' && $fileId > 0) {
    $file = File::find((int) $fileId);
    if ($file) {
        $file->delete();
        $_SESSION['success'] = 'File deleted successfully';
    }
    header('Location: files.php');
    exit;
}

if ($action === 'toggle_featured' && $fileId > 0) {
    $file = File::find((int) $fileId);
    if ($file) {
        $file->setAsFeatured(!$file->is_featured);
        $_SESSION['success'] = 'File featured status updated successfully';
    }
    header('Location: files.php');
    exit;
}

if ($action === 'toggle_premium' && $fileId > 0) {
    $file = File::find((int) $fileId);
    if ($file) {
        $file->setAsPremium(!$file->is_premium);
        $_SESSION['success'] = 'File premium status updated successfully';
    }
    header('Location: files.php');
    exit;
}

if ($action === 'toggle_verified' && $fileId > 0) {
    $file = File::find((int) $fileId);
    if ($file) {
        $file->setAsVerified(!$file->is_verified);
        $_SESSION['success'] = 'File verification status updated successfully';
    }
    header('Location: files.php');
    exit;
}

if ($action === 'publish' && $fileId > 0) {
    $file = File::find((int) $fileId);
    if ($file) {
        $file->publish();
        $_SESSION['success'] = 'File published successfully';
    }
    header('Location: files.php');
    exit;
}

if ($action === 'unpublish' && $fileId > 0) {
    $file = File::find((int) $fileId);
    if ($file) {
        $file->unpublish();
        $_SESSION['success'] = 'File unpublished successfully';
    }
    header('Location: files.php');
    exit;
}

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Search and filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = ['f.deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(f.name LIKE :search OR f.display_name LIKE :search OR f.description LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($category) {
    $where[] = "f.category = :category";
    $params[':category'] = $category;
}

if ($brand) {
    $where[] = "f.brand_id = :brand";
    $params[':brand'] = $brand;
}

if ($status) {
    $where[] = "f.is_active = :status";
    $params[':status'] = $status === 'active' ? 1 : 0;
}

$whereClause = implode(' AND ', $where);

// Get files with brand and model information
$sql = "
    SELECT f.*, b.name as brand_name, m.name as model_name 
    FROM files f 
    LEFT JOIN brands b ON f.brand_id = b.id 
    LEFT JOIN models m ON f.model_id = m.id 
    WHERE {$whereClause} 
    ORDER BY f.created_at DESC 
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
$stmt->execute();
$files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM files f WHERE {$whereClause}";
$countStmt = $db->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalFiles = $countStmt->fetchColumn();
$totalPages = ceil($totalFiles / $limit);

// Get statistics
$stats = [
    'total' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL"),
    'active' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE is_active = 1 AND deleted_at IS NULL"),
    'featured' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE is_featured = 1 AND deleted_at IS NULL"),
    'premium' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE is_premium = 1 AND deleted_at IS NULL"),
    'verified' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE is_verified = 1 AND deleted_at IS NULL"),
    'total_downloads' => (int) $db->fetchColumn("SELECT COALESCE(SUM(download_count), 0) FROM files WHERE deleted_at IS NULL"),
];

// Get categories for filter dropdown
$categories = $db->fetchAll("SELECT DISTINCT category FROM files WHERE deleted_at IS NULL ORDER BY category");

// Get brands for filter dropdown
$brands = $db->fetchAll("SELECT id, name FROM brands WHERE deleted_at IS NULL ORDER BY name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files Management - IMEI Bot Platform</title>
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
                    <a href="files.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-900">Files</a>
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
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Files Management</h1>
                <p class="text-gray-600">Manage platform files and downloads</p>
            </div>
            <a href="file-upload.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-upload mr-2"></i>Upload New File
            </a>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-500 rounded-full">
                        <i class="fas fa-file text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Files</p>
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
                        <p class="text-sm text-gray-600">Active</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-500 rounded-full">
                        <i class="fas fa-star text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Featured</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['featured']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-500 rounded-full">
                        <i class="fas fa-crown text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Premium</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['premium']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-500 rounded-full">
                        <i class="fas fa-download text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Downloads</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_downloads']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <input type="text" name="search" placeholder="Search files..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category']; ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Brands</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo $brand === $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
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

        <!-- Files Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Files (<?php echo number_format($totalFiles); ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand/Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Downloads</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($file['display_name'] ?: $file['name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($file['version'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo ucfirst($file['category']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div><?php echo htmlspecialchars($file['brand_name'] ?: 'Unknown'); ?></div>
                                        <div class="text-gray-500"><?php echo htmlspecialchars($file['model_name'] ?: ''); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format((float) $file['file_size_mb'], 1); ?> MB
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format((int) $file['download_count']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex space-x-1">
                                        <?php if ($file['is_featured']): ?>
                                            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">Featured</span>
                                        <?php endif; ?>
                                        <?php if ($file['is_premium']): ?>
                                            <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">Premium</span>
                                        <?php endif; ?>
                                        <?php if ($file['is_verified']): ?>
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Verified</span>
                                        <?php endif; ?>
                                        <?php if (!$file['is_active']): ?>
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="file-details.php?id=<?php echo $file['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=toggle_featured&id=<?php echo $file['id']; ?>" 
                                           class="text-yellow-600 hover:text-yellow-900"
                                           title="Toggle Featured">
                                            <i class="fas fa-star"></i>
                                        </a>
                                        <a href="?action=toggle_premium&id=<?php echo $file['id']; ?>" 
                                           class="text-purple-600 hover:text-purple-900"
                                           title="Toggle Premium">
                                            <i class="fas fa-crown"></i>
                                        </a>
                                        <a href="?action=toggle_verified&id=<?php echo $file['id']; ?>" 
                                           class="text-green-600 hover:text-green-900"
                                           title="Toggle Verified">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                        <?php if ($file['is_active']): ?>
                                            <a href="?action=unpublish&id=<?php echo $file['id']; ?>" 
                                               class="text-orange-600 hover:text-orange-900"
                                               title="Unpublish">
                                                <i class="fas fa-eye-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=publish&id=<?php echo $file['id']; ?>" 
                                               class="text-green-600 hover:text-green-900"
                                               title="Publish">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $file['id']; ?>" 
                                           class="text-red-600 hover:text-red-900"
                                           onclick="return confirm('Are you sure you want to delete this file?')">
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
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalFiles); ?> of <?php echo $totalFiles; ?> results
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&brand=<?php echo urlencode($brand); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&brand=<?php echo urlencode($brand); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 border <?php echo $i === $page ? 'bg-blue-500 text-white' : 'border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&brand=<?php echo urlencode($brand); ?>&status=<?php echo urlencode($status); ?>" 
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
