<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure application monitoring settings including performance
    | tracking, error reporting, and health checks.
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring settings to track application
    | performance metrics and identify bottlenecks.
    |
    */

    'performance' => [
        'enabled' => true,
        'track_response_time' => true,
        'track_memory_usage' => true,
        'track_database_queries' => true,
        'track_cache_operations' => true,
        'slow_query_threshold' => 1000, // milliseconds
        'slow_request_threshold' => 5000, // milliseconds
        'memory_threshold' => 128, // MB
        'sample_rate' => 1.0, // 100% sampling
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure error monitoring and reporting settings to track
    | application errors and exceptions.
    |
    */

    'errors' => [
        'enabled' => true,
        'track_exceptions' => true,
        'track_fatal_errors' => true,
        'track_warnings' => false,
        'track_deprecations' => false,
        'exclude_patterns' => [
            '/^Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\NotFoundHttpException/',
            '/^Illuminate\\\\Database\\\\Eloquent\\\\ModelNotFoundException/',
        ],
        'include_context' => true,
        'include_stack_trace' => true,
        'include_request_data' => true,
        'include_session_data' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | Configure health check settings to monitor application
    | and service health.
    |
    */

    'health_checks' => [
        'enabled' => true,
        'interval' => 60, // seconds
        'timeout' => 10, // seconds
        'checks' => [
            'database' => [
                'enabled' => true,
                'timeout' => 5,
                'max_failures' => 3,
            ],
            'cache' => [
                'enabled' => true,
                'timeout' => 3,
                'max_failures' => 3,
            ],
            'redis' => [
                'enabled' => true,
                'timeout' => 3,
                'max_failures' => 3,
            ],
            'storage' => [
                'enabled' => true,
                'timeout' => 5,
                'max_failures' => 3,
                'disk_space_threshold' => 90, // percentage
            ],
            'external_apis' => [
                'enabled' => true,
                'timeout' => 10,
                'max_failures' => 3,
                'endpoints' => [
                    'telegram' => 'https://api.telegram.org/bot',
                    'whatsapp' => 'https://graph.facebook.com',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configure metrics collection settings for application
    | performance and usage analytics.
    |
    */

    'metrics' => [
        'enabled' => true,
        'collection_interval' => 60, // seconds
        'retention_period' => 30, // days
        'metrics' => [
            'request_count' => true,
            'response_time' => true,
            'error_rate' => true,
            'throughput' => true,
            'concurrent_users' => true,
            'memory_usage' => true,
            'cpu_usage' => true,
            'database_connections' => true,
            'cache_hit_rate' => true,
            'file_operations' => true,
            'bot_interactions' => true,
            'imei_generations' => true,
            'file_downloads' => true,
        ],
        'dimensions' => [
            'endpoint',
            'method',
            'status_code',
            'user_type',
            'bot_platform',
            'file_type',
            'imei_prefix',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure alerting settings for notifications when
    | thresholds are exceeded or issues are detected.
    |
    */

    'alerting' => [
        'enabled' => true,
        'channels' => [
            'email' => [
                'enabled' => true,
                'to' => env('ADMIN_EMAIL', 'admin@imei-bot.com'),
                'from' => env('ALERT_FROM_EMAIL', 'alerts@imei-bot.com'),
            ],
            'slack' => [
                'enabled' => false,
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
                'channel' => '#alerts',
            ],
            'telegram' => [
                'enabled' => true,
                'chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
            ],
        ],
        'thresholds' => [
            'error_rate' => [
                'warning' => 5, // percentage
                'critical' => 10, // percentage
                'time_window' => 300, // seconds
            ],
            'response_time' => [
                'warning' => 2000, // milliseconds
                'critical' => 5000, // milliseconds
                'time_window' => 300, // seconds
            ],
            'memory_usage' => [
                'warning' => 80, // percentage
                'critical' => 90, // percentage
            ],
            'disk_usage' => [
                'warning' => 80, // percentage
                'critical' => 90, // percentage
            ],
            'database_connections' => [
                'warning' => 80, // percentage
                'critical' => 90, // percentage
            ],
        ],
        'cooldown' => 300, // seconds between same alert
        'max_alerts_per_hour' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging settings for monitoring data and events.
    |
    */

    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'channels' => [
            'performance' => 'monitoring_performance',
            'errors' => 'monitoring_errors',
            'health_checks' => 'monitoring_health',
            'metrics' => 'monitoring_metrics',
            'alerts' => 'monitoring_alerts',
        ],
        'format' => 'json',
        'include_stack_trace' => true,
        'max_files' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configure dashboard settings for monitoring visualization
    | and real-time metrics display.
    |
    */

    'dashboard' => [
        'enabled' => true,
        'refresh_interval' => 30, // seconds
        'time_ranges' => [
            '1h' => 'Last Hour',
            '24h' => 'Last 24 Hours',
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
        ],
        'widgets' => [
            'overview' => [
                'request_count',
                'error_rate',
                'response_time',
                'active_users',
            ],
            'performance' => [
                'response_time_chart',
                'throughput_chart',
                'memory_usage_chart',
                'cpu_usage_chart',
            ],
            'infrastructure' => [
                'database_status',
                'cache_status',
                'storage_status',
                'external_apis_status',
            ],
            'business' => [
                'imei_generations',
                'file_downloads',
                'bot_interactions',
                'user_registrations',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Services Integration
    |--------------------------------------------------------------------------
    |
    | Configure integration with external monitoring services
    | like New Relic, DataDog, Sentry, etc.
    |
    */

    'services' => [
        'new_relic' => [
            'enabled' => env('NEW_RELIC_ENABLED', false),
            'license_key' => env('NEW_RELIC_LICENSE_KEY'),
            'app_name' => env('NEW_RELIC_APP_NAME', 'IMEI Bot Platform'),
            'log_level' => 'info',
        ],
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
            'host' => env('DATADOG_HOST', 'api.datadoghq.com'),
            'tags' => [
                'env' => env('APP_ENV', 'production'),
                'service' => 'imei-bot',
            ],
        ],
        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_LARAVEL_DSN'),
            'environment' => env('APP_ENV', 'production'),
            'sample_rate' => 1.0,
            'traces_sample_rate' => 0.1,
        ],
        'prometheus' => [
            'enabled' => env('PROMETHEUS_ENABLED', false),
            'port' => env('PROMETHEUS_PORT', 9090),
            'path' => '/metrics',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Configuration
    |--------------------------------------------------------------------------
    |
    | Configure data retention settings for monitoring data
    | to manage storage usage and comply with regulations.
    |
    */

    'retention' => [
        'metrics' => 30, // days
        'logs' => 90, // days
        'alerts' => 365, // days
        'health_checks' => 7, // days
        'performance_data' => 30, // days
        'cleanup_interval' => '0 2 * * *', // Daily at 2 AM
    ],
];
