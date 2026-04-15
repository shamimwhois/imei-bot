-- Create rate_limits table for platform-specific rate limiting
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `identifier` VARCHAR(100) NOT NULL, -- user_id, IP address, or API key
    `identifier_type` ENUM('user_id', 'ip_address', 'api_key', 'session_id') NOT NULL,
    `platform` ENUM('telegram', 'whatsapp', 'web', 'api', 'admin') NOT NULL,
    `action` VARCHAR(50) NOT NULL, -- imei_generation, file_download, api_call, etc.
    `limit_count` INT UNSIGNED NOT NULL,
    `limit_period` INT UNSIGNED NOT NULL, -- in seconds
    `current_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `reset_at` TIMESTAMP NOT NULL,
    `is_blocked` BOOLEAN NOT NULL DEFAULT FALSE,
    `blocked_until` TIMESTAMP NULL,
    `block_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_rate_limits_identifier` (`identifier`),
    INDEX `idx_rate_limits_identifier_type` (`identifier_type`),
    INDEX `idx_rate_limits_platform` (`platform`),
    INDEX `idx_rate_limits_action` (`action`),
    INDEX `idx_rate_limits_reset_at` (`reset_at`),
    INDEX `idx_rate_limits_is_blocked` (`is_blocked`),
    INDEX `idx_rate_limits_blocked_until` (`blocked_until`),
    INDEX `idx_rate_limits_created_at` (`created_at`),
    
    UNIQUE KEY `uk_rate_limits_identifier_platform_action` (`identifier`, `platform`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
