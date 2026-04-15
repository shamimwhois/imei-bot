-- Create settings table for dynamic application configuration
CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` LONGTEXT NULL,
    `type` ENUM('string', 'integer', 'float', 'boolean', 'json', 'array', 'text') NOT NULL DEFAULT 'string',
    `group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` TEXT NULL,
    `is_public` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_editable` BOOLEAN NOT NULL DEFAULT TRUE,
    `validation_rules` JSON NULL,
    `default_value` LONGTEXT NULL,
    `options` JSON NULL, -- For select/enum type values
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX `idx_settings_key` (`key`),
    INDEX `idx_settings_group` (`group`),
    INDEX `idx_settings_is_public` (`is_public`),
    INDEX `idx_settings_is_editable` (`is_editable`),
    INDEX `idx_settings_sort_order` (`sort_order`),
    INDEX `idx_settings_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
