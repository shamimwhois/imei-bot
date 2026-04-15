-- Create models table for mobile device models
CREATE TABLE IF NOT EXISTS `models` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `model_number` VARCHAR(50) NULL,
    `codename` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `image_url` VARCHAR(255) NULL,
    `release_date` DATE NULL,
    `discontinued_date` DATE NULL,
    `device_type` ENUM('smartphone', 'feature_phone', 'tablet', 'phablet', 'foldable', 'other') NOT NULL DEFAULT 'smartphone',
    `os_type` ENUM('android', 'ios', 'windows', 'blackberry', 'symbian', 'other') NOT NULL DEFAULT 'android',
    `network_technology` JSON NULL, -- 2G, 3G, 4G, 5G support
    `sim_type` ENUM('single', 'dual', 'hybrid', 'esim') NOT NULL DEFAULT 'single',
    `processor` VARCHAR(100) NULL,
    `ram_mb` INT UNSIGNED NULL,
    `storage_gb` JSON NULL, -- Available storage options
    `display_size_inches` DECIMAL(4,2) NULL,
    `display_resolution` VARCHAR(20) NULL,
    `camera_megapixels` JSON NULL, -- Main and front camera specs
    `battery_mah` INT UNSIGNED NULL,
    `weight_grams` INT UNSIGNED NULL,
    `dimensions_mm` JSON NULL, -- Length, width, thickness
    `colors` JSON NULL, -- Available color options
    `price_range` JSON NULL, -- Price range at release
    `is_popular` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `meta_keywords` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_models_brand_id` (`brand_id`),
    INDEX `idx_models_name` (`name`),
    INDEX `idx_models_slug` (`slug`),
    INDEX `idx_models_model_number` (`model_number`),
    INDEX `idx_models_device_type` (`device_type`),
    INDEX `idx_models_os_type` (`os_type`),
    INDEX `idx_models_is_popular` (`is_popular`),
    INDEX `idx_models_is_active` (`is_active`),
    INDEX `idx_models_release_date` (`release_date`),
    INDEX `idx_models_sort_order` (`sort_order`),
    INDEX `idx_models_created_at` (`created_at`),
    
    UNIQUE KEY `uk_models_brand_slug` (`brand_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
