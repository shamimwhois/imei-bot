<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

try {
    $db = Database::getInstance(
        require __DIR__ . '/../config/database.php',
        new Logger(require __DIR__ . '/../config/monitoring.php')
    );
    
    echo "Creating files table...\n";
    
    $sql = "
        CREATE TABLE IF NOT EXISTS `files` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `uuid` VARCHAR(36) NOT NULL UNIQUE,
            `brand_id` BIGINT UNSIGNED NULL,
            `model_id` BIGINT UNSIGNED NULL,
            `category` ENUM('firmware', 'qcn', 'nvram', 'solution', 'tool', 'driver', 'manual', 'other') NOT NULL DEFAULT 'firmware',
            `subcategory` VARCHAR(100) NULL,
            `name` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL,
            `display_name` VARCHAR(255) NULL,
            `description` TEXT NULL,
            `version` VARCHAR(50) NULL,
            `build_number` VARCHAR(50) NULL,
            `android_version` VARCHAR(20) NULL,
            `security_patch` VARCHAR(20) NULL,
            `region` VARCHAR(50) NULL,
            `carrier` VARCHAR(100) NULL,
            `network_lock` ENUM('unlocked', 'locked', 'carrier_locked') NOT NULL DEFAULT 'unlocked',
            `file_type` ENUM('zip', 'rar', '7z', 'tar', 'gz', 'exe', 'bin', 'img', 'other') NOT NULL DEFAULT 'zip',
            `file_size_bytes` BIGINT UNSIGNED NULL,
            `file_size_mb` DECIMAL(10,2) GENERATED ALWAYS AS (file_size_bytes / 1048576) STORED,
            `file_hash_md5` VARCHAR(32) NULL,
            `file_hash_sha256` VARCHAR(64) NULL,
            `file_path` VARCHAR(500) NULL,
            `file_url` VARCHAR(500) NULL,
            `cdn_url` VARCHAR(500) NULL,
            `download_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `rating` DECIMAL(3,2) NULL,
            `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
            `is_premium` BOOLEAN NOT NULL DEFAULT FALSE,
            `is_verified` BOOLEAN NOT NULL DEFAULT FALSE,
            `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
            `is_virus_scanned` BOOLEAN NOT NULL DEFAULT FALSE,
            `virus_scan_result` JSON NULL,
            `upload_source` ENUM('admin', 'user', 'import', 'api') NOT NULL DEFAULT 'admin',
            `uploaded_by` BIGINT UNSIGNED NULL,
            `approved_by` BIGINT UNSIGNED NULL,
            `approved_at` TIMESTAMP NULL,
            `published_at` TIMESTAMP NULL,
            `expires_at` TIMESTAMP NULL,
            `access_level` ENUM('public', 'premium', 'admin') NOT NULL DEFAULT 'public',
            `download_limit` INT UNSIGNED NULL,
            `download_limit_period` ENUM('daily', 'weekly', 'monthly') NULL,
            `tags` JSON NULL,
            `compatibility` JSON NULL,
            `requirements` JSON NULL,
            `changelog` TEXT NULL,
            `installation_notes` TEXT NULL,
            `meta_title` VARCHAR(255) NULL,
            `meta_description` TEXT NULL,
            `meta_keywords` VARCHAR(500) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL,
            
            INDEX `idx_files_brand_id` (`brand_id`),
            INDEX `idx_files_model_id` (`model_id`),
            INDEX `idx_files_category` (`category`),
            INDEX `idx_files_slug` (`slug`),
            INDEX `idx_files_is_active` (`is_active`),
            INDEX `idx_files_is_featured` (`is_featured`),
            INDEX `idx_files_is_premium` (`is_premium`),
            INDEX `idx_files_is_verified` (`is_verified`),
            INDEX `idx_files_published_at` (`published_at`),
            INDEX `idx_files_expires_at` (`expires_at`),
            INDEX `idx_files_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($sql);
    echo "Files table created successfully!\n";
    
    // Add some sample data
    echo "Adding sample data...\n";
    
    $sampleFiles = [
        [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'brand_id' => 1,
            'model_id' => 1,
            'category' => 'firmware',
            'name' => 'Samsung Galaxy S21 Firmware',
            'slug' => 'samsung-galaxy-s21-firmware',
            'display_name' => 'Samsung Galaxy S21 SM-G991B Firmware',
            'description' => 'Official firmware for Samsung Galaxy S21',
            'version' => '5.0',
            'file_size_bytes' => 2147483648,
            'download_count' => 1250,
            'is_featured' => 1,
            'is_active' => 1
        ],
        [
            'uuid' => '550e8400-e29b-41d4-a716-446655440001',
            'brand_id' => 1,
            'model_id' => 2,
            'category' => 'qcn',
            'name' => 'Samsung Galaxy S22 QCN',
            'slug' => 'samsung-galaxy-s22-qcn',
            'display_name' => 'Samsung Galaxy S22 QCN File',
            'description' => 'QCN file for Samsung Galaxy S22',
            'file_size_bytes' => 1048576,
            'download_count' => 890,
            'is_featured' => 1,
            'is_active' => 1
        ],
        [
            'uuid' => '550e8400-e29b-41d4-a716-446655440002',
            'brand_id' => 2,
            'model_id' => 3,
            'category' => 'firmware',
            'name' => 'iPhone 13 Firmware',
            'slug' => 'iphone-13-firmware',
            'display_name' => 'iPhone 13 iOS Firmware',
            'description' => 'Official iOS firmware for iPhone 13',
            'version' => '16.0',
            'file_size_bytes' => 4294967296,
            'download_count' => 2100,
            'is_featured' => 1,
            'is_active' => 1
        ]
    ];
    
    foreach ($sampleFiles as $file) {
        $columns = array_keys($file);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO files (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($file));
    }
    
    echo "Sample data added successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
