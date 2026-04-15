-- Create imei_logs table for tracking IMEI generation
CREATE TABLE IF NOT EXISTS `imei_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `user_id` BIGINT UNSIGNED NULL,
    `platform` ENUM('telegram', 'whatsapp', 'web', 'api', 'admin') NOT NULL,
    `imei` VARCHAR(15) NOT NULL,
    `prefix` VARCHAR(2) NOT NULL,
    `type` ENUM('random', 'single', 'dual', 'multi', 'validate') NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `batch_uuid` VARCHAR(36) NULL, -- For multi-IMEI generation batches
    `is_valid` BOOLEAN NOT NULL DEFAULT TRUE,
    `validation_method` ENUM('luhn', 'gs1', 'custom', 'none') NOT NULL DEFAULT 'luhn',
    `generation_time_ms` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX `idx_imei_logs_user_id` (`user_id`),
    INDEX `idx_imei_logs_platform` (`platform`),
    INDEX `idx_imei_logs_imei` (`imei`),
    INDEX `idx_imei_logs_prefix` (`prefix`),
    INDEX `idx_imei_logs_type` (`type`),
    INDEX `idx_imei_logs_batch_uuid` (`batch_uuid`),
    INDEX `idx_imei_logs_is_valid` (`is_valid`),
    INDEX `idx_imei_logs_validation_method` (`validation_method`),
    INDEX `idx_imei_logs_created_at` (`created_at`),
    
    UNIQUE KEY `uk_imei_logs_imei_user` (`imei`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
