-- Create commands table for bot command management
CREATE TABLE IF NOT EXISTS `commands` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `platform` ENUM('telegram', 'whatsapp', 'web', 'api', 'all') NOT NULL DEFAULT 'all',
    `category` ENUM('imei_generation', 'file_downloads', 'user_management', 'admin_commands', 'navigation', 'other') NOT NULL,
    `handler_class` VARCHAR(255) NOT NULL,
    `method_name` VARCHAR(100) NOT NULL DEFAULT 'handle',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `is_admin_only` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_premium_only` BOOLEAN NOT NULL DEFAULT FALSE,
    `requires_auth` BOOLEAN NOT NULL DEFAULT FALSE,
    `rate_limit_per_minute` INT UNSIGNED NULL,
    `rate_limit_per_hour` INT UNSIGNED NULL,
    `parameters` JSON NULL, -- Command parameters schema
    `examples` JSON NULL, -- Usage examples
    `aliases` JSON NULL, -- Command aliases
    `middleware` JSON NULL, -- Middleware classes
    `response_type` ENUM('text', 'photo', 'video', 'audio', 'document', 'sticker', 'animation', 'voice', 'video_note', 'location', 'venue', 'contact', 'poll', 'venue', 'invoice', 'successful_payment', 'connected_website', 'passport_data', 'proximity_alert_triggered', 'video_chat_started', 'video_chat_ended', 'video_chat_participants_invited', 'video_chat_scheduled', 'message_auto_delete_timer_changed', 'migrate_to_chat_id', 'migrate_from_chat_id', 'pinned_message', 'new_chat_members', 'left_chat_member', 'new_chat_title', 'new_chat_photo', 'delete_chat_photo', 'group_chat_created', 'supergroup_chat_created', 'channel_chat_created', 'migrate_to_chat_id', 'migrate_from_chat_id', 'pinned_message', 'new_chat_members', 'left_chat_member', 'new_chat_title', 'new_chat_photo', 'delete_chat_photo', 'group_chat_created', 'supergroup_chat_created', 'channel_chat_created', 'migrate_to_chat_id', 'migrate_from_chat_id', 'pinned_message', 'new_chat_members', 'left_chat_member', 'new_chat_title', 'new_chat_photo', 'delete_chat_photo', 'group_chat_created', 'supergroup_chat_created', 'channel_chat_created') NOT NULL DEFAULT 'text',
    `keyboard_type` ENUM('none', 'reply_keyboard', 'inline_keyboard', 'force_reply', 'remove_keyboard') NOT NULL DEFAULT 'none',
    `keyboard_layout` JSON NULL,
    `parse_mode` ENUM('none', 'markdown', 'markdownv2', 'html') NOT NULL DEFAULT 'html',
    `disable_web_page_preview` BOOLEAN NOT NULL DEFAULT TRUE,
    `disable_notification` BOOLEAN NOT NULL DEFAULT FALSE,
    `allow_sending_without_reply` BOOLEAN NOT NULL DEFAULT FALSE,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX `idx_commands_name` (`name`),
    INDEX `idx_commands_platform` (`platform`),
    INDEX `idx_commands_category` (`category`),
    INDEX `idx_commands_is_active` (`is_active`),
    INDEX `idx_commands_is_admin_only` (`is_admin_only`),
    INDEX `idx_commands_is_premium_only` (`is_premium_only`),
    INDEX `idx_commands_sort_order` (`sort_order`),
    INDEX `idx_commands_created_at` (`created_at`),
    
    UNIQUE KEY `uk_commands_platform_name` (`platform`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
