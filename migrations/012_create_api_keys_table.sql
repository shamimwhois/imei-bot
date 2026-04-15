-- Create api_keys table for API key management
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `key_hash` VARCHAR(64) NOT NULL UNIQUE,
    `key_prefix` VARCHAR(8) NOT NULL,
    `permissions` JSON NULL, -- Array of allowed permissions
    `rate_limit_per_hour` INT UNSIGNED NULL,
    `rate_limit_per_day` INT UNSIGNED NULL,
    `allowed_ips` JSON NULL, -- Array of allowed IP addresses
    `allowed_domains` JSON NULL, -- Array of allowed domains
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `expires_at` TIMESTAMP NULL,
    `last_used_at` TIMESTAMP NULL,
    `usage_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX `idx_api_keys_user_id` (`user_id`),
    INDEX `idx_api_keys_name` (`name`),
    INDEX `idx_api_keys_key_hash` (`key_hash`),
    INDEX `idx_api_keys_key_prefix` (`key_prefix`),
    INDEX `idx_api_keys_is_active` (`is_active`),
    INDEX `idx_api_keys_expires_at` (`expires_at`),
    INDEX `idx_api_keys_last_used_at` (`last_used_at`),
    INDEX `idx_api_keys_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
