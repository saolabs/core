<?php

return [
    /*
    |--------------------------------------------------------------------------
    | One Core Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình chính cho thư viện One Core
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình database cho các bảng của One Core
    |
    */
    'database' => [
        'connection' => env('ONE_DB_CONNECTION', 'default'),
        'prefix' => env('ONE_TABLE_PREFIX', 'one_'),
        'migrations' => [
            'auto_load' => env('ONE_AUTO_LOAD_MIGRATIONS', true),
            'publish_path' => env('ONE_MIGRATIONS_PATH', 'database/migrations'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cache cho One Core
    |
    */
    'cache' => [
        'default_ttl' => env('ONE_CACHE_TTL', 3600),
        'driver' => env('ONE_CACHE_DRIVER', 'file'),
        'prefix' => env('ONE_CACHE_PREFIX', 'one_'),
        'compress' => env('ONE_CACHE_COMPRESS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | ShortCode Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho ShortCode Engine
    |
    */
    'shortcode' => [
        'enabled' => env('ONE_SHORTCODE_ENABLED', true),
        'cache_enabled' => env('ONE_SHORTCODE_CACHE', true),
        'cache_ttl' => env('ONE_SHORTCODE_CACHE_TTL', 1800),
        'ignore_html' => env('ONE_SHORTCODE_IGNORE_HTML', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Management Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho File Management System
    |
    */
    'files' => [
        'upload_path' => env('ONE_UPLOAD_PATH', 'uploads'),
        'max_size' => env('ONE_MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_types' => env('ONE_ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx,txt'),
        'log_operations' => env('ONE_LOG_FILE_OPERATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình bảo mật cho One Core
    |
    */
    'security' => [
        'encrypt_sensitive_data' => env('ONE_ENCRYPT_SENSITIVE', true),
        'log_security_events' => env('ONE_LOG_SECURITY', true),
        'rate_limiting' => [
            'enabled' => env('ONE_RATE_LIMITING', true),
            'max_attempts' => env('ONE_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('ONE_DECAY_MINUTES', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho Laravel Octane compatibility
    |
    */
    'octane' => [
        'enabled' => env('ONE_OCTANE_ENABLED', false),
        'reset_static_state' => env('ONE_RESET_STATIC_STATE', true),
        'reset_services_state' => env('ONE_RESET_SERVICES_STATE', true),
        'memory_limit' => env('ONE_MEMORY_LIMIT', '512M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình logging cho One Core
    |
    */
    'logging' => [
        'enabled' => env('ONE_LOGGING_ENABLED', true),
        'channel' => env('ONE_LOG_CHANNEL', 'daily'),
        'level' => env('ONE_LOG_LEVEL', 'info'),
        'max_files' => env('ONE_LOG_MAX_FILES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình performance cho One Core
    |
    */
    'performance' => [
        'query_cache' => env('ONE_QUERY_CACHE', true),
        'view_cache' => env('ONE_VIEW_CACHE', true),
        'route_cache' => env('ONE_ROUTE_CACHE', true),
        'config_cache' => env('ONE_CONFIG_CACHE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Internationalization Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình đa ngôn ngữ cho One Core
    |
    */
    'i18n' => [
        'default_locale' => env('ONE_DEFAULT_LOCALE', 'vi'),
        'fallback_locale' => env('ONE_FALLBACK_LOCALE', 'en'),
        'available_locales' => explode(',', env('ONE_AVAILABLE_LOCALES', 'vi,en')),
        'auto_detect' => env('ONE_AUTO_DETECT_LOCALE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho API features
    |
    */
    'api' => [
        'rate_limiting' => env('ONE_API_RATE_LIMITING', true),
        'throttle' => env('ONE_API_THROTTLE', '60,1'),
        'cors' => [
            'enabled' => env('ONE_API_CORS', true),
            'origins' => explode(',', env('ONE_API_CORS_ORIGINS', '*')),
            'methods' => explode(',', env('ONE_API_CORS_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho development mode
    |
    */
    'development' => [
        'debug_mode' => env('ONE_DEBUG_MODE', false),
        'show_queries' => env('ONE_SHOW_QUERIES', false),
        'log_queries' => env('ONE_LOG_QUERIES', false),
        'profiler' => env('ONE_PROFILER', false),
    ],
]; 