<?php

// config for StreamPulse/StreamPulse
return [
    /*
    |--------------------------------------------------------------------------
    | Default Stream Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default stream driver that will be used for
    | event streaming. Supported drivers: "redis", "null"
    |
    */
    'default' => env('STREAMPULSE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Stream Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the stream drivers for your application.
    | Available drivers: "redis"
    |
    */
    'drivers' => [
        'redis' => [
            'connection' => env('REDIS_CONNECTION', 'localhost:6379'),
            'stream_prefix' => 'streampulse:',
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
        'route_prefix' => 'streampulse',
    ],
];
