<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'debug' => true,
    
    'telegram' => [
        'enabled' => true,
        'token' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '',
        'webhook_url' => $_ENV['TELEGRAM_WEBHOOK_URL'] ?? '',
        'admin_chat_id' => $_ENV['TELEGRAM_ADMIN_CHAT_ID'] ?? '',
        'api_url' => 'https://api.telegram.org/bot',
        'file_url' => 'https://api.telegram.org/file/bot',
    ],
    
    'whatsapp' => [
        'enabled' => false,
        'phone_id' => $_ENV['WHATSAPP_PHONE_ID'] ?? '',
        'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? '',
        'webhook_url' => $_ENV['WHATSAPP_WEBHOOK_URL'] ?? '',
        'verify_token' => $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '',
        'api_url' => 'https://graph.facebook.com/v18.0',
    ],
    
    'commands' => [
        'start' => 'Start using the bot',
        'help' => 'Show help information',
        'random' => 'Generate random IMEI',
        'single' => 'Generate single IMEI',
        'dual' => 'Generate dual IMEI',
        'validate' => 'Validate IMEI',
        'profile' => 'View your profile',
        'premium' => 'Premium features',
        'settings' => 'Personal settings',
        'feedback' => 'Send feedback',
        'menu' => 'Show main menu',
        'back' => 'Go back to previous menu',
    ],
    
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 30,
        'requests_per_hour' => 200,
        'whitelist_admins' => true,
    ],
    
    'features' => [
        'file_downloads' => true,
        'imei_generation' => true,
        'user_registration' => true,
        'analytics_tracking' => true,
        'inline_queries' => false,
        'groups_support' => false,
        'channel_support' => true,
        'web_app_support' => true,
    ],
];
