<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of cached items together.
    |
    | Supported drivers: "apc", "array", "database", "file",
    |         "memcached", "redis", "octane", "dynamodb"
    |
    */

    'stores' => [
        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'prefix' => '',
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the RAM, APC, database, or file cache drivers we
    | typically prefix each cache key with a prefix to prevent collisions.
    | This prefix can be customized per cache store.
    |
    */

    'prefix' => env('CACHE_PREFIX', str_replace('\\', '_', __NAMESPACE__.'_cache')),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default TTL (Time To Live) values for different types of
    | cached data. These values can be overridden on a per-key basis.
    |
    */

    'ttl' => [
        'default' => env('CACHE_TTL', 3600), // 1 hour
        'user_sessions' => 1800, // 30 minutes
        'menu_cache' => 900, // 15 minutes
        'file_cache' => 7200, // 2 hours
        'statistics_cache' => 300, // 5 minutes
        'imei_validation' => 86400, // 24 hours
        'bot_commands' => 3600, // 1 hour
        'api_responses' => 1800, // 30 minutes
        'templates' => 86400, // 24 hours
        'config' => 86400, // 24 hours
        'rate_limits' => 60, // 1 minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache tags for better cache management and invalidation.
    | Tags allow you to group related cache items and clear them together.
    |
    */

    'tags' => [
        'users' => ['users', 'sessions', 'profiles'],
        'files' => ['files', 'downloads', 'uploads'],
        'imei' => ['imei', 'generation', 'validation'],
        'bots' => ['telegram', 'whatsapp', 'commands'],
        'analytics' => ['stats', 'metrics', 'reports'],
        'system' => ['config', 'settings', 'maintenance'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Locking Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache locking settings to prevent cache stampede and
    | ensure data consistency when multiple processes try to update
    | the same cache key simultaneously.
    |
    */

    'locks' => [
        'enabled' => true,
        'default_timeout' => 10, // seconds
        'max_wait_time' => 30, // seconds
        'retry_delay' => 100, // milliseconds
        'max_retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Compression Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache compression settings to reduce memory usage and
    | improve performance for large cached items.
    |
    */

    'compression' => [
        'enabled' => true,
        'threshold' => 1024, // bytes
        'level' => 6, // 1-9 (9 = highest compression)
        'algorithm' => 'gzip', // gzip, deflate, bzip2
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache monitoring and metrics collection for performance
    | analysis and optimization.
    |
    */

    'monitoring' => [
        'enabled' => env('APP_DEBUG', false),
        'collect_metrics' => true,
        'log_slow_operations' => true,
        'slow_operation_threshold' => 100, // milliseconds
        'track_hit_rates' => true,
        'alert_on_low_hit_rate' => true,
        'hit_rate_threshold' => 80, // percentage
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warm-up Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache warm-up settings to pre-populate frequently
    | accessed data for better performance.
    |
    */

    'warmup' => [
        'enabled' => true,
        'on_boot' => false,
        'schedule' => '0 */6 * * *', // Every 6 hours
        'keys' => [
            'app.config',
            'bot.commands',
            'file.categories',
            'imei.prefixes',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache cleanup settings to remove expired or invalid
    | cache entries and maintain optimal performance.
    |
    */

    'cleanup' => [
        'enabled' => true,
        'schedule' => '0 2 * * *', // Daily at 2 AM
        'batch_size' => 1000,
        'max_execution_time' => 300, // seconds
        'cleanup_expired' => true,
        'cleanup_orphaned' => true,
        'vacuum' => true,
    ],
];
