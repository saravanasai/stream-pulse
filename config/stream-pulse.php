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
            'connection' => env('REDIS_CONNECTION', 'default'),
            'stream_prefix' => 'streampulse:',
        ],
    ],
];
