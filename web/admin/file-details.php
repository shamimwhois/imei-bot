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

// Get file ID from URL parameter
$fileId = $_GET['id'] ?? 0;

// Validate and cast to integer
$fileIdInt = (int) $fileId;

if ($fileIdInt <= 0) {
    $_SESSION['error'] = 'Invalid file ID';
    header('Location: files.php');
    exit;
}

// Get file details
$file = File::find($fileIdInt);

if (!$file) {
    $_SESSION['error'] = 'File not found';
    header('Location: files.php');
    exit;
}

// Get brand and model information
$brand = null;
$model = null;

if ($file->brand_id) {
    $brand = Brand::find($file->brand_id);
}

if ($file->model_id) {
    $model = Model::find($file->model_id);
}

// Handle file actions
$action = $_POST['action'] ?? '';

if ($action === 'toggle_featured') {
    $file->setAsFeatured(!$file->is_featured);
    $_SESSION['success'] = 'File featured status updated successfully';
    header("Location: file-details.php?id=$fileIdInt");
    exit;
}

if ($action === 'toggle_premium') {
    $file->setAsPremium(!$file->is_premium);
    $_SESSION['success'] = 'File premium status updated successfully';
    header("Location: file-details.php?id=$fileIdInt");
    exit;
}

if ($action === 'toggle_verified') {
    $file->setAsVerified(!$file->is_verified);
    $_SESSION['success'] = 'File verification status updated successfully';
    header("Location: file-details.php?id=$fileIdInt");
    exit;
}

if ($action === 'publish') {
    $file->publish();
    $_SESSION['success'] = 'File published successfully';
    header("Location: file-details.php?id=$fileIdInt");
    exit;
}

if ($action === 'unpublish') {
    $file->unpublish();
    $_SESSION['success'] = 'File unpublished successfully';
    header("Location: file-details.php?id=$fileIdInt");
    exit;
}

if ($action === 'delete') {
    $file->delete();
    $_SESSION['success'] = 'File deleted successfully';
    header('Location: files.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Details - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">IMEI Bot Platform</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="files.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Files
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- File Details -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">File Details</h3>
                    
                    <!-- File Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($file->display_name ?? $file->name); ?></h2>
                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($file->description ?? ''); ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($file->is_featured): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-star mr-1"></i>Featured
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($file->is_premium): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-crown mr-1"></i>Premium
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($file->is_verified): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>Verified
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($file->is_active): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-eye mr-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-eye-slash mr-1"></i>Inactive
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- File Information Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Basic Information -->
                        <div class="col-span-1">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Basic Information</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">File ID:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo $file->id; ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Internal Name:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->name); ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Category:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars(ucfirst($file->category)); ?></dd>
                                </div>
                                <?php if ($file->subcategory): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Subcategory:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->subcategory); ?></dd>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">File Type:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars(strtoupper($file->file_type)); ?></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Version Information -->
                        <div class="col-span-1">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Version Information</h4>
                            <dl class="space-y-2">
                                <?php if ($file->version): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Version:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->version); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if ($file->build_number): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Build:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->build_number); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if ($file->android_version): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Android:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->android_version); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if ($file->security_patch): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Security Patch:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->security_patch); ?></dd>
                                </div>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <!-- Device Information -->
                        <div class="col-span-1">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Device Information</h4>
                            <dl class="space-y-2">
                                <?php if ($brand): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Brand:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($brand->name); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if ($model): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Model:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($model->name); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if ($file->region): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Region:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->region); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if ($file->carrier): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Carrier:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($file->carrier); ?></dd>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Network Lock:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars(ucfirst($file->network_lock)); ?></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- File Statistics -->
                        <div class="col-span-1">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Statistics</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">File Size:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo number_format((float) $file->file_size_mb, 2); ?> MB</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Downloads:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo number_format((int) $file->download_count); ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Views:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo number_format((int) $file->view_count); ?></dd>
                                </div>
                                <?php if ($file->rating): ?>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Rating:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo number_format((float) $file->rating, 1); ?>/5</dd>
                                </div>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <!-- File Hashes -->
                        <div class="col-span-1">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">File Hashes</h4>
                            <dl class="space-y-2">
                                <?php if ($file->file_hash_md5): ?>
                                <div>
                                    <dt class="text-sm text-gray-500">MD5:</dt>
                                    <dd class="text-sm text-gray-900 font-mono break-all"><?php echo htmlspecialchars($file->file_hash_md5); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if ($file->file_hash_sha256): ?>
                                <div>
                                    <dt class="text-sm text-gray-500">SHA256:</dt>
                                    <dd class="text-sm text-gray-900 font-mono break-all text-xs"><?php echo htmlspecialchars($file->file_hash_sha256); ?></dd>
                                </div>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <!-- Timestamps -->
                        <div class="col-span-1">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Timestamps</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Created:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($file->created_at)); ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Updated:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($file->updated_at)); ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <form method="POST" class="space-x-3">
                            <input type="hidden" name="action" value="toggle_featured">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-star mr-2"></i>
                                <?php echo $file->is_featured ? 'Remove from Featured' : 'Add to Featured'; ?>
                            </button>
                        </form>

                        <form method="POST" class="space-x-3 inline">
                            <input type="hidden" name="action" value="toggle_premium">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-crown mr-2"></i>
                                <?php echo $file->is_premium ? 'Remove Premium' : 'Make Premium'; ?>
                            </button>
                        </form>

                        <form method="POST" class="space-x-3 inline">
                            <input type="hidden" name="action" value="toggle_verified">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-check mr-2"></i>
                                <?php echo $file->is_verified ? 'Remove Verification' : 'Verify File'; ?>
                            </button>
                        </form>

                        <?php if ($file->is_active): ?>
                        <form method="POST" class="space-x-3 inline">
                            <input type="hidden" name="action" value="unpublish">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye-slash mr-2"></i>
                                Unpublish
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" class="space-x-3 inline">
                            <input type="hidden" name="action" value="publish">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye mr-2"></i>
                                Publish
                            </button>
                        </form>
                        <?php endif; ?>

                        <form method="POST" class="space-x-3 inline" onsubmit="return confirm('Are you sure you want to delete this file? This action cannot be undone.')">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-trash mr-2"></i>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
