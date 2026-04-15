<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Telegram bot settings including API token, webhook URL,
    | and other bot-specific options. Make sure to set your bot token
    | in the .env file for security.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
    'api_url' => 'https://api.telegram.org/bot',
    'file_url' => 'https://api.telegram.org/file/bot',

    /*
    |--------------------------------------------------------------------------
    | Bot Commands Configuration
    |--------------------------------------------------------------------------
    |
    | Define the available bot commands and their descriptions. These will
    | be automatically registered with Telegram when you set the webhook.
    | Commands are organized by category for better user experience.
    |
    */

    'commands' => [
        'imei_generation' => [
            [
                'command' => 'random',
                'description' => 'Generate a random IMEI number',
                'handler' => IMEIBot\Commands\RandomIMEICommand::class,
            ],
        [
            'command' => 'single',
            'description' => 'Generate a single IMEI with prefix selection',
            'handler' => IMEIBot\Commands\SingleIMEICommand::class,
        ],
        [
            'command' => 'dual',
            'description' => 'Generate a dual IMEI pair',
            'handler' => IMEIBot\Commands\DualIMEICommand::class,
        ],
        [
            'command' => 'multi',
            'description' => 'Generate multiple IMEIs',
            'handler' => IMEIBot\Commands\MultiIMEICommand::class,
        ],
        [
            'command' => 'validate',
            'description' => 'Validate an existing IMEI number',
            'handler' => IMEIBot\Commands\ValidateIMEICommand::class,
        ],
        [
            'command' => 'history',
            'description' => 'View your IMEI generation history',
            'handler' => IMEIBot\Commands\HistoryCommand::class,
        ],
        ],

        'file_downloads' => [
            [
                'command' => 'firmware',
                'description' => 'Browse firmware files',
                'handler' => IMEIBot\Commands\FirmwareCommand::class,
            ],
            [
                'command' => 'engrom',
                'description' => 'Browse engineering ROM files',
                'handler' => IMEIBot\Commands\EngRomCommand::class,
            ],
            [
                'command' => 'qcnnvram',
                'description' => 'Browse QCN and NVRAM files',
                'handler' => IMEIBot\Commands\QCNCommand::class,
            ],
            [
                'command' => 'solution',
                'description' => 'Browse solution files',
                'handler' => IMEIBot\Commands\SolutionCommand::class,
            ],
            [
                'command' => 'search',
                'description' => 'Search for files',
                'handler' => IMEIBot\Commands\SearchCommand::class,
            ],
            [
                'command' => 'recent',
                'description' => 'View latest uploaded files',
                'handler' => IMEIBot\Commands\RecentCommand::class,
            ],
            [
                'command' => 'popular',
                'description' => 'View most downloaded files',
                'handler' => IMEIBot\Commands\PopularCommand::class,
            ],
        ],

        'user_management' => [
            [
                'command' => 'start',
                'description' => 'Start using the bot',
                'handler' => IMEIBot\Commands\StartCommand::class,
            ],
            [
                'command' => 'help',
                'description' => 'Show help information',
                'handler' => IMEIBot\Commands\HelpCommand::class,
            ],
            [
                'command' => 'profile',
                'description' => 'View your profile',
                'handler' => IMEIBot\Commands\ProfileCommand::class,
            ],
            [
                'command' => 'premium',
                'description' => 'Premium features',
                'handler' => IMEIBot\Commands\PremiumCommand::class,
            ],
            [
                'command' => 'settings',
                'description' => 'Personal settings',
                'handler' => IMEIBot\Commands\SettingsCommand::class,
            ],
            [
                'command' => 'feedback',
                'description' => 'Send feedback',
                'handler' => IMEIBot\Commands\FeedbackCommand::class,
            ],
        ],

        'admin_commands' => [
            [
                'command' => 'admin',
                'description' => 'Admin dashboard',
                'handler' => IMEIBot\Commands\Admin\AdminCommand::class,
                'admin_only' => true,
            ],
            [
                'command' => 'stats',
                'description' => 'View statistics',
                'handler' => IMEIBot\Commands\Admin\StatsCommand::class,
                'admin_only' => true,
            ],
            [
                'command' => 'broadcast',
                'description' => 'Send broadcast message',
                'handler' => IMEIBot\Commands\Admin\BroadcastCommand::class,
                'admin_only' => true,
            ],
            [
                'command' => 'maintenance',
                'description' => 'Toggle maintenance mode',
                'handler' => IMEIBot\Commands\Admin\MaintenanceCommand::class,
                'admin_only' => true,
            ],
            [
                'command' => 'backup',
                'description' => 'Trigger manual backup',
                'handler' => IMEIBot\Commands\Admin\BackupCommand::class,
                'admin_only' => true,
            ],
        ],

        'navigation' => [
            [
                'command' => 'menu',
                'description' => 'Show main menu',
                'handler' => IMEIBot\Commands\MenuCommand::class,
            ],
            [
                'command' => 'back',
                'description' => 'Go back to previous menu',
                'handler' => IMEIBot\Commands\BackCommand::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Behavior Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the bot behaves in different situations including
    | rate limiting, message formatting, and user interaction settings.
    |
    */

    'behavior' => [
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 30,
            'requests_per_hour' => 200,
            'whitelist_admins' => true,
        ],
        'message_formatting' => [
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'disable_notification' => false,
        ],
        'keyboard_settings' => [
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
            'selective' => false,
        ],
        'inline_keyboard_settings' => [
            'inline_keyboard' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific bot features based on your requirements
    | or environment. This allows for flexible configuration across
    | different deployment scenarios.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Bot Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security settings for the Telegram bot including
    | user verification, spam protection, and data validation.
    |
    */

    'security' => [
        'verify_users' => true,
        'block_spam' => true,
        'max_message_length' => 4096,
        'allowed_updates' => ['message', 'callback_query', 'inline_query'],
        'secret_token' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging settings for the Telegram bot including
    | which events to log and log levels.
    |
    */

    'logging' => [
        'enabled' => true,
        'log_level' => 'info',
        'log_all_updates' => false,
        'log_user_actions' => true,
        'log_errors' => true,
        'log_performance' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching settings for the Telegram bot to improve
    | performance and reduce API calls.
    |
    */

    'cache' => [
        'enabled' => true,
        'user_sessions' => 3600, // 1 hour
        'menu_cache' => 1800, // 30 minutes
        'file_cache' => 7200, // 2 hours
        'statistics_cache' => 300, // 5 minutes
    ],
];
