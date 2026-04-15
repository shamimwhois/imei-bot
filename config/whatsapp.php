<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your WhatsApp Business API settings including phone number ID,
    | access token, and webhook settings. These settings are required for
    | WhatsApp integration functionality.
    |
    */

    'phone_number_id' => env('WHATSAPP_PHONE_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),
    'api_version' => 'v18.0',
    'base_url' => 'https://graph.facebook.com',

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Message Templates
    |--------------------------------------------------------------------------
    |
    | Define message templates that can be used for sending notifications
    | and automated messages through WhatsApp. Templates must be approved
    | by WhatsApp before they can be used.
    |
    */

    'templates' => [
        'welcome' => [
            'name' => 'welcome_message',
            'language' => 'en',
            'components' => [
                [
                    'type' => 'body',
                    'text' => 'Welcome to IMEI Bot Platform! Your account has been successfully registered. Use /help to see available commands.',
                ],
            ],
        ],
        'imei_generated' => [
            'name' => 'imei_generated',
            'language' => 'en',
            'components' => [
                [
                    'type' => 'body',
                    'text' => 'Your IMEI has been generated: {{1}}. Use /validate to check its validity.',
                ],
            ],
        ],
        'file_ready' => [
            'name' => 'file_ready',
            'language' => 'en',
            'components' => [
                [
                    'type' => 'body',
                    'text' => 'Your requested file is ready for download: {{1}}. Click the link to download.',
                ],
                [
                    'type' => 'button',
                    'text' => 'Download',
                    'url' => '{{2}}',
                ],
            ],
        ],
        'premium_reminder' => [
            'name' => 'premium_reminder',
            'language' => 'en',
            'components' => [
                [
                    'type' => 'body',
                    'text' => 'Upgrade to premium to unlock unlimited IMEI generation and file downloads. Visit {{1}} to learn more.',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Interactive Messages
    |--------------------------------------------------------------------------
    |
    | Configure interactive message types and buttons for enhanced user
    | experience in WhatsApp. These provide rich interaction capabilities
    | beyond simple text messages.
    |
    */

    'interactive_messages' => [
        'quick_replies' => [
            'imei_options' => [
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'random_imei',
                        'title' => 'Random IMEI',
                    ],
                ],
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'single_imei',
                        'title' => 'Single IMEI',
                    ],
                ],
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'dual_imei',
                        'title' => 'Dual IMEI',
                    ],
                ],
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'multi_imei',
                        'title' => 'Multiple IMEIs',
                    ],
                ],
            ],
            'file_options' => [
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'firmware',
                        'title' => 'Firmware',
                    ],
                ],
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'engrom',
                        'title' => 'Engineering ROM',
                    ],
                ],
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'qcnnvram',
                        'title' => 'QCN/NVRAM',
                    ],
                ],
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'solution',
                        'title' => 'Solutions',
                    ],
                ],
            ],
        ],
        'list_messages' => [
            'main_menu' => [
                'header' => [
                    'type' => 'text',
                    'text' => 'Main Menu',
                ],
                'body' => [
                    'text' => 'Choose an option from the menu below:',
                ],
                'footer' => [
                    'text' => 'IMEI Bot Platform',
                ],
                'action' => [
                    'button' => 'Menu',
                    'sections' => [
                        [
                            'title' => 'IMEI Services',
                            'rows' => [
                                [
                                    'id' => 'random_imei',
                                    'title' => 'Random IMEI',
                                    'description' => 'Generate a random IMEI number',
                                ],
                                [
                                    'id' => 'single_imei',
                                    'title' => 'Single IMEI',
                                    'description' => 'Generate IMEI with prefix selection',
                                ],
                                [
                                    'id' => 'dual_imei',
                                    'title' => 'Dual IMEI',
                                    'description' => 'Generate dual IMEI pair',
                                ],
                            ],
                        ],
                        [
                            'title' => 'File Downloads',
                            'rows' => [
                                [
                                    'id' => 'firmware',
                                    'title' => 'Firmware',
                                    'description' => 'Browse firmware files',
                                ],
                                [
                                    'id' => 'engrom',
                                    'title' => 'Engineering ROM',
                                    'description' => 'Browse engineering ROM files',
                                ],
                                [
                                    'id' => 'qcnnvram',
                                    'title' => 'QCN/NVRAM',
                                    'description' => 'Browse QCN and NVRAM files',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Media Configuration
    |--------------------------------------------------------------------------
    |
    | Configure media handling settings for WhatsApp including file size
    | limits, supported formats, and storage settings.
    |
    */

    'media' => [
        'max_file_size' => 100 * 1024 * 1024, // 100MB
        'supported_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'video' => ['mp4', '3gp'],
            'audio' => ['mp3', 'wav', 'ogg'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        ],
        'compression' => [
            'enabled' => true,
            'quality' => 80,
            'max_width' => 1920,
            'max_height' => 1080,
        ],
        'storage' => [
            'driver' => 's3',
            'path' => 'whatsapp/media',
            'cache_ttl' => 86400, // 24 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting settings to prevent hitting WhatsApp API limits.
    | These settings help ensure reliable message delivery.
    |
    */

    'rate_limits' => [
        'messages_per_second' => 50,
        'messages_per_minute' => 1000,
        'messages_per_hour' => 10000,
        'messages_per_day' => 100000,
        'template_messages_per_day' => 10000,
        'marketing_messages_per_day' => 1000,
        'burst_protection' => true,
        'adaptive_throttling' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook settings for receiving messages and status updates
    | from WhatsApp. These settings ensure proper message handling.
    |
    */

    'webhook' => [
        'events' => [
            'messages',
            'message_reactions',
            'message_edits',
            'message_status',
            'account_update',
            'phone_number_name_update',
            'phone_number_quality_update',
        ],
        'field_names' => [
            'messages',
            'messaging_phone_number',
            'message_reactions',
            'message_edits',
            'message_status',
            'account_update',
            'phone_number_name_update',
            'phone_number_quality_update',
        ],
        'verify_ssl' => true,
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security settings for WhatsApp integration including
    | encryption, access control, and data protection.
    |
    */

    'security' => [
        'encrypt_messages' => true,
        'verify_webhooks' => true,
        'access_control' => [
            'allowed_ips' => [],
            'blocked_ips' => [],
            'rate_limit_by_ip' => true,
        ],
        'data_retention' => [
            'messages' => 30, // days
            'media' => 7, // days
            'metadata' => 90, // days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure analytics settings for tracking WhatsApp bot performance
    | and user engagement metrics.
    |
    */

    'analytics' => [
        'enabled' => true,
        'track_events' => [
            'message_sent',
            'message_received',
            'template_sent',
            'interactive_clicked',
            'media_shared',
        ],
        'track_metrics' => [
            'delivery_rate',
            'read_rate',
            'response_rate',
            'conversion_rate',
        ],
        'retention_period' => 90, // days
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Profile
    |--------------------------------------------------------------------------
    |
    | Configure business profile settings for WhatsApp including
    | business information and contact details.
    |
    */

    'business_profile' => [
        'about' => 'Professional IMEI generation and file download platform',
        'address' => 'Global Service',
        'description' => 'Advanced IMEI generation and mobile file download platform with multi-platform bot support',
        'email' => 'support@imei-bot.com',
        'websites' => [
            env('APP_URL'),
        ],
        'vertical' => 'PROFESSIONAL_SERVICES',
    ],
];
