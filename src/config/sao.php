<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Saola Core Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình chính cho thư viện Saola Core
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình database cho các bảng của Saola Core
    |
    */
    'database' => [
        'connection' => env('SAO_DB_CONNECTION', 'default'),
        'prefix' => env('SAO_TABLE_PREFIX', 'sao_'),
        'migrations' => [
            'auto_load' => env('SAO_AUTO_LOAD_MIGRATIONS', true),
            'publish_path' => env('SAO_MIGRATIONS_PATH', 'database/migrations'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cache cho Saola Core
    |
    */
    'cache' => [
        'default_ttl' => env('SAO_CACHE_TTL', 3600),
        'driver' => env('SAO_CACHE_DRIVER', 'file'),
        'prefix' => env('SAO_CACHE_PREFIX', 'sao_'),
        'compress' => env('SAO_CACHE_COMPRESS', false),
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
        'enabled' => env('SAO_SHORTCODE_ENABLED', true),
        'cache_enabled' => env('SAO_SHORTCODE_CACHE', true),
        'cache_ttl' => env('SAO_SHORTCODE_CACHE_TTL', 1800),
        'ignore_html' => env('SAO_SHORTCODE_IGNORE_HTML', false),
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
        'upload_path' => env('SAO_UPLOAD_PATH', 'uploads'),
        'max_size' => env('SAO_MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_types' => env('SAO_ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx,txt'),
        'log_operations' => env('SAO_LOG_FILE_OPERATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình bảo mật cho Saola Core
    |
    */
    'security' => [
        'encrypt_sensitive_data' => env('SAO_ENCRYPT_SENSITIVE', true),
        'log_security_events' => env('SAO_LOG_SECURITY', true),
        'rate_limiting' => [
            'enabled' => env('SAO_RATE_LIMITING', true),
            'max_attempts' => env('SAO_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('SAO_DECAY_MINUTES', 1),
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
        'enabled' => env('SAO_OCTANE_ENABLED', false),
        'reset_static_state' => env('SAO_RESET_STATIC_STATE', true),
        'reset_services_state' => env('SAO_RESET_SERVICES_STATE', true),
        'memory_limit' => env('SAO_MEMORY_LIMIT', '512M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình logging cho Saola Core
    |
    */
    'logging' => [
        'enabled' => env('SAO_LOGGING_ENABLED', true),
        'channel' => env('SAO_LOG_CHANNEL', 'daily'),
        'level' => env('SAO_LOG_LEVEL', 'info'),
        'max_files' => env('SAO_LOG_MAX_FILES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình performance cho Saola Core
    |
    */
    'performance' => [
        'query_cache' => env('SAO_QUERY_CACHE', true),
        'view_cache' => env('SAO_VIEW_CACHE', true),
        'route_cache' => env('SAO_ROUTE_CACHE', true),
        'config_cache' => env('SAO_CONFIG_CACHE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Internationalization Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình đa ngôn ngữ cho Saola Core
    |
    */
    'i18n' => [
        'default_locale' => env('SAO_DEFAULT_LOCALE', 'vi'),
        'fallback_locale' => env('SAO_FALLBACK_LOCALE', 'en'),
        'available_locales' => explode(',', env('SAO_AVAILABLE_LOCALES', 'vi,en')),
        'auto_detect' => env('SAO_AUTO_DETECT_LOCALE', true),
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
        'rate_limiting' => env('SAO_API_RATE_LIMITING', true),
        'throttle' => env('SAO_API_THROTTLE', '60,1'),
        'cors' => [
            'enabled' => env('SAO_API_CORS', true),
            'origins' => explode(',', env('SAO_API_CORS_ORIGINS', '*')),
            'methods' => explode(',', env('SAO_API_CORS_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho development mode của Saola Core
    |
    */
    'development' => [
        'debug_mode' => env('SAO_DEBUG_MODE', false),
        'show_queries' => env('SAO_SHOW_QUERIES', false),
        'log_queries' => env('SAO_LOG_QUERIES', false),
        'profiler' => env('SAO_PROFILER', false),
    ],
]; 