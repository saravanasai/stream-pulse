<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | StreamPulse supports multiple backends. Choose which driver to use
    | globally. Available: "redis", "nats"
    |
    */
    'driver' => env('STREAMPULSE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When strict mode is enabled, only topics explicitly defined in the
    | configuration can be used. This prevents accidental topic creation
    | in production environments.
    |
    */
    'strict_mode' => env('STREAMPULSE_STRICT_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Global Defaults
    |--------------------------------------------------------------------------
    |
    | These settings apply to all topics unless overridden below.
    |
    */
    'defaults' => [
        'max_retries' => 3,
        'dlq' => 'dead_letter',
        'retention' => 1000, // Redis only: default max length
    ],

    /*
    |--------------------------------------------------------------------------
    | Topics
    |--------------------------------------------------------------------------
    |
    | Define per-topic configuration. Each topic can override retry count,
    | DLQ destination, and retention policy.
    |
    */
    'topics' => [
        'orders' => [
            'max_retries' => 5,
            'dlq' => 'orders_dlq',
            'retention' => 5000,
        ],
        'notifications' => [
            'max_retries' => 2,
            'retention' => 2000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    |
    | Backend-specific configuration. Redis has retention settings,
    | NATS is just a placeholder for now.
    |
    */
    'drivers' => [
        'redis' => [
            'connection' => env('REDIS_CONNECTION', 'default'),
            'stream_prefix' => 'streampulse:',
        ],

        'nats' => [
            'servers' => env('NATS_SERVERS', 'nats://127.0.0.1:4222'),
            // Future: retention, streams, consumer groups
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to the StreamPulse UI dashboard.
    |
    */
    'ui' => [
        'enabled' => env('STREAMPULSE_UI_ENABLED', true),
        'page_size' => env('STREAMPULSE_UI_PAGE_SIZE', 50),
        'route_prefix' => 'stream-pulse',
    ],
];
