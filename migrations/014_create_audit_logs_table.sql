-- Create audit_logs table for comprehensive audit trail
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL, -- user, file, setting, etc.
    `entity_id` VARCHAR(36) NULL, -- UUID of the entity
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `platform` ENUM('telegram', 'whatsapp', 'web', 'api', 'admin', 'system') NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `session_id` VARCHAR(64) NULL,
    `request_id` VARCHAR(64) NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX `idx_audit_logs_user_id` (`user_id`),
    INDEX `idx_audit_logs_action` (`action`),
    INDEX `idx_audit_logs_entity_type` (`entity_type`),
    INDEX `idx_audit_logs_entity_id` (`entity_id`),
    INDEX `idx_audit_logs_platform` (`platform`),
    INDEX `idx_audit_logs_ip_address` (`ip_address`),
    INDEX `idx_audit_logs_session_id` (`session_id`),
    INDEX `idx_audit_logs_request_id` (`request_id`),
    INDEX `idx_audit_logs_created_at` (`created_at`),
    
    -- Partitioning for better performance with large datasets
    PARTITION BY RANGE (YEAR(`created_at`)) (
        PARTITION p2024 VALUES LESS THAN (2025),
        PARTITION p2025 VALUES LESS THAN (2026),
        PARTITION p2026 VALUES LESS THAN (2027),
        PARTITION p2027 VALUES LESS THAN (2028),
        PARTITION p_future VALUES LESS THAN MAXVALUE
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
