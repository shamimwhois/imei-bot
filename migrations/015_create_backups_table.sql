-- Create backups table for backup management
CREATE TABLE IF NOT EXISTS `backups` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('full', 'incremental', 'database', 'files', 'config', 'logs') NOT NULL,
    `status` ENUM('pending', 'running', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `location` VARCHAR(500) NOT NULL, -- Storage location (local, S3, etc.)
    `file_path` VARCHAR(500) NULL,
    `file_size_bytes` BIGINT UNSIGNED NULL,
    `file_size_mb` DECIMAL(10,2) GENERATED ALWAYS AS (file_size_bytes / 1048576) STORED,
    `compression` ENUM('none', 'gzip', 'zip', '7z') NOT NULL DEFAULT 'gzip',
    `encryption` ENUM('none', 'aes256', 'ssl') NOT NULL DEFAULT 'none',
    `checksum_md5` VARCHAR(32) NULL,
    `checksum_sha256` VARCHAR(64) NULL,
    `backup_config` JSON NULL, -- Backup configuration and options
    `included_tables` JSON NULL, -- List of included database tables
    `excluded_tables` JSON NULL, -- List of excluded database tables
    `included_paths` JSON NULL, -- List of included file paths
    `excluded_paths` JSON NULL, -- List of excluded file paths
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `duration_seconds` INT UNSIGNED NULL,
    `error_message` TEXT NULL,
    `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_retries` INT UNSIGNED NOT NULL DEFAULT 3,
    `next_retry_at` TIMESTAMP NULL,
    `retention_days` INT UNSIGNED NOT NULL DEFAULT 30,
    `expires_at` TIMESTAMP NULL,
    `is_automatic` BOOLEAN NOT NULL DEFAULT FALSE,
    `schedule` VARCHAR(100) NULL, -- Cron expression
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX `idx_backups_name` (`name`),
    INDEX `idx_backups_type` (`type`),
    INDEX `idx_backups_status` (`status`),
    INDEX `idx_backups_location` (`location`),
    INDEX `idx_backups_started_at` (`started_at`),
    INDEX `idx_backups_completed_at` (`completed_at`),
    INDEX `idx_backups_expires_at` (`expires_at`),
    INDEX `idx_backups_is_automatic` (`is_automatic`),
    INDEX `idx_backups_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
