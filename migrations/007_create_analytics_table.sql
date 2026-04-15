-- Create analytics table for usage tracking and metrics
CREATE TABLE IF NOT EXISTS `analytics` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `event_type` ENUM('imei_generated', 'file_downloaded', 'bot_command', 'user_registered', 'user_login', 'page_view', 'api_call', 'error_occurred', 'other') NOT NULL,
    `platform` ENUM('telegram', 'whatsapp', 'web', 'api', 'admin') NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `session_id` VARCHAR(64) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `country` VARCHAR(2) NULL,
    `city` VARCHAR(100) NULL,
    `device_type` ENUM('desktop', 'mobile', 'tablet', 'bot', 'other') NULL,
    `browser` VARCHAR(50) NULL,
    `os` VARCHAR(50) NULL,
    `event_data` JSON NULL, -- Event-specific data
    `metadata` JSON NULL, -- Additional metadata
    `referrer` VARCHAR(500) NULL,
    `utm_source` VARCHAR(100) NULL,
    `utm_medium` VARCHAR(100) NULL,
    `utm_campaign` VARCHAR(100) NULL,
    `utm_term` VARCHAR(100) NULL,
    `utm_content` VARCHAR(100) NULL,
    `response_time_ms` INT UNSIGNED NULL,
    `status_code` INT UNSIGNED NULL,
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX `idx_analytics_event_type` (`event_type`),
    INDEX `idx_analytics_platform` (`platform`),
    INDEX `idx_analytics_user_id` (`user_id`),
    INDEX `idx_analytics_session_id` (`session_id`),
    INDEX `idx_analytics_ip_address` (`ip_address`),
    INDEX `idx_analytics_country` (`country`),
    INDEX `idx_analytics_device_type` (`device_type`),
    INDEX `idx_analytics_browser` (`browser`),
    INDEX `idx_analytics_os` (`os`),
    INDEX `idx_analytics_created_at` (`created_at`),
    INDEX `idx_analytics_status_code` (`status_code`),
    
    -- Partitioning for better performance with large datasets
    PARTITION BY RANGE (YEAR(`created_at`)) (
        PARTITION p2024 VALUES LESS THAN (2025),
        PARTITION p2025 VALUES LESS THAN (2026),
        PARTITION p2026 VALUES LESS THAN (2027),
        PARTITION p2027 VALUES LESS THAN (2028),
        PARTITION p_future VALUES LESS THAN MAXVALUE
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
