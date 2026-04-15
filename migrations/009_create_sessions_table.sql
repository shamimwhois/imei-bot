-- Create sessions table for user session management
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(64) NOT NULL PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT UNSIGNED NOT NULL,
    `platform` ENUM('web', 'telegram', 'whatsapp', 'api', 'admin') NOT NULL DEFAULT 'web',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    
    INDEX `idx_sessions_user_id` (`user_id`),
    INDEX `idx_sessions_ip_address` (`ip_address`),
    INDEX `idx_sessions_platform` (`platform`),
    INDEX `idx_sessions_is_active` (`is_active`),
    INDEX `idx_sessions_expires_at` (`expires_at`),
    INDEX `idx_sessions_last_activity` (`last_activity`),
    INDEX `idx_sessions_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
