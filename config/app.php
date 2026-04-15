<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'IMEI Bot Platform'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [
        // Core Service Providers
        IMEIBot\Core\Database::class,
        IMEIBot\Core\Cache::class,
        IMEIBot\Core\Auth::class,
        IMEIBot\Core\Logger::class,

        // Service Providers
        IMEIBot\Services\IMEIGenerator::class,
        IMEIBot\Services\FileManager::class,
        IMEIBot\Services\SearchService::class,
        IMEIBot\Services\AnalyticsService::class,
        IMEIBot\Services\NotificationService::class,
        IMEIBot\Services\BackupService::class,

        // Bot Providers
        IMEIBot\Core\TelegramBot::class,
        IMEIBot\Core\WhatsAppBot::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [
        'App' => IMEIBot\Facades\App::class,
        'DB' => IMEIBot\Facades\Database::class,
        'Cache' => IMEIBot\Facades\Cache::class,
        'Log' => IMEIBot\Facades\Log::class,
        'Validator' => IMEIBot\Facades\Validator::class,
        'Telegram' => IMEIBot\Facades\Telegram::class,
        'WhatsApp' => IMEIBot\Facades\WhatsApp::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure which features are enabled for your application.
    | This allows you to easily toggle features on and off based on your
    | environment or specific deployment requirements.
    |
    */

    'features' => [
        'whatsapp' => env('ENABLE_WHATSAPP', false),
        'analytics' => env('ENABLE_ANALYTICS', true),
        'backup' => env('ENABLE_BACKUP', true),
        'api_docs' => env('ENABLE_API_DOCS', true),
        'dark_mode' => env('ENABLE_DARK_MODE', true),
        'multi_language' => env('ENABLE_MULTI_LANGUAGE', true),
        'imei_validation' => env('ENABLE_IMEI_VALIDATION', true),
        'file_scanning' => env('ENABLE_FILE_SCANNING', true),
        'rate_limiting' => env('ENABLE_RATE_LIMITING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting settings for different user types and actions.
    | These settings help prevent abuse and ensure fair usage of resources.
    |
    */

    'rate_limits' => [
        'default' => [
            'requests' => env('RATE_LIMIT_REQUESTS', 100),
            'minutes' => env('RATE_LIMIT_MINUTES', 1),
        ],
        'imei_generation' => [
            'requests' => 10,
            'minutes' => 1,
        ],
        'file_download' => [
            'requests' => 20,
            'minutes' => 5,
        ],
        'api_calls' => [
            'requests' => 1000,
            'minutes' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure file upload settings including maximum file size, allowed
    | file types, and storage paths. These settings help ensure security
    | and proper organization of uploaded files.
    |
    */

    'uploads' => [
        'max_size' => env('MAX_FILE_SIZE', '100MB'),
        'allowed_types' => explode(',', env('ALLOWED_FILE_TYPES', 'zip,7z,rar,tar,gz,bin,img,cert,qcn,nvram')),
        'upload_path' => env('UPLOAD_PATH', 'storage/uploads'),
        'temp_path' => env('TEMP_PATH', 'storage/uploads/temp'),
        'scan_files' => env('ENABLE_FILE_SCANNING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | IMEI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure IMEI generation settings including supported prefixes,
    | maximum generation limits, and validation settings.
    |
    */

    'imei' => [
        'prefixes' => explode(',', env('IMEI_PREFIXES', '35,86,89,45')),
        'max_per_request' => env('MAX_IMEI_PER_REQUEST', 50),
        'enable_validation' => env('ENABLE_IMEI_VALIDATION', true),
        'cache_validated' => true,
        'cache_ttl' => 86400, // 24 hours
    ],
];
