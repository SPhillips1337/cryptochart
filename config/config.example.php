<?php
/**
 * Configuration file for Cryptochart application
 * Copy this file to config.php and adjust settings as needed
 */

return [
    // API Configuration
    'api' => [
        'binance_base_url' => 'https://api.binance.com/api/v3',
        'default_symbol' => 'ETHUSDT',
        'default_interval' => '1d',
        'default_limit' => 500,
        'timeout' => 30, // seconds
        'user_agent' => 'Cryptochart/1.0',
    ],

    // Technical Indicators Configuration
    'indicators' => [
        'ema' => [
            'fast_period' => 25,
            'slow_period' => 100,
        ],
        'stoch_rsi' => [
            'rsi_period' => 14,
            'k_period' => 3,
            'd_period' => 3,
        ],
        'macd' => [
            'fast_period' => 12,
            'slow_period' => 26,
            'signal_period' => 9,
        ],
    ],

    // Caching Configuration
    'cache' => [
        'enabled' => true,
        'type' => 'file', // 'file' or 'redis'
        'ttl' => 300, // 5 minutes in seconds
        'directory' => __DIR__ . '/../cache',
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
        ],
    ],

    // Rate Limiting Configuration
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000,
        'storage' => 'file', // 'file' or 'redis'
    ],

    // Security Configuration
    'security' => [
        'cors_enabled' => true,
        'allowed_origins' => ['*'], // Use specific domains in production
        'allowed_methods' => ['GET', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
        'max_age' => 3600,
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'file' => __DIR__ . '/../logs/app.log',
        'max_files' => 10,
        'max_size' => '10MB',
    ],

    // Chart Configuration
    'chart' => [
        'colors' => [
            'close' => '#000000',
            'ema_fast' => '#0066CC',
            'ema_slow' => '#CC0000',
            'stoch_rsi' => '#00CC00',
            'macd' => '#9900CC',
            'signal' => '#FF6600',
            'histogram' => '#FFCC00',
        ],
        'responsive' => true,
        'animation' => true,
    ],

    // Environment Configuration
    'environment' => 'development', // 'development' or 'production'
    'debug' => true,
    'timezone' => 'UTC',
];