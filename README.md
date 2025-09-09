# StreamPulse

[![Latest Version on Packagist](https://img.shields.io/packagist/v/saravanasai/stream-pulse.svg?style=flat-square)](https://packagist.org/packages/saravanasai/stream-pulse)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/saravanasai/stream-pulse/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/saravanasai/stream-pulse/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/saravanasai/stream-pulse/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/saravanasai/stream-pulse/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/saravanasai/stream-pulse.svg?style=flat-square)](https://packagist.org/packages/saravanasai/stream-pulse)

StreamPulse is a Laravel package for event streaming with support for multiple drivers. It provides a simple, unified API for publishing and consuming events across different streaming platforms.

## Features

-   Simple, Laravel-style API for event streaming
-   Redis Streams driver implementation
-   Consumer group support for distributed event processing
-   Dead letter queue for failed message handling
-   Extensible architecture to support additional drivers

## Installation

You can install the package via composer:

```bash
composer require saravanasai/stream-pulse
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="stream-pulse-config"
```

This is the contents of the published config file:

```php
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
```

## Usage

### Publishing Events

Publish events to a specified topic:

```php
use StreamPulse\StreamPulse\Facades\StreamPulse;

// Publish an event
StreamPulse::publish('orders', [
    'id' => 1,
    'status' => 'created',
    'customer_id' => 123,
    'amount' => 99.99,
]);
```

### Consuming Events

Consume events from a topic with a consumer group:

```php
use StreamPulse\StreamPulse\Facades\StreamPulse;

// Consume events
StreamPulse::consume('orders', 'billing-service', function ($event, $messageId) {
    // Process the event
    Log::info('Processing order: ' . $event['id']);

    // After successful processing, acknowledge the message
    StreamPulse::ack('orders', $messageId, 'billing-service');
});
```

### Error Handling

If processing fails, you can mark a message as failed:

```php
use StreamPulse\StreamPulse\Facades\StreamPulse;

StreamPulse::consume('orders', 'billing-service', function ($event, $messageId) {
    try {
        // Process the event
        processOrder($event);

        // Acknowledge successful processing
        StreamPulse::ack('orders', $messageId, 'billing-service');
    } catch (\Exception $e) {
        // Mark as failed (will move to dead letter queue)
        StreamPulse::fail('orders', $messageId, 'billing-service');
        logger()->error('Failed to process order: ' . $e->getMessage());
    }
});
```

## Redis Streams Implementation

StreamPulse uses Redis Streams as the default driver, which provides:

-   Persistent message storage
-   Consumer groups for distributed processing
-   Automatic tracking of processed messages
-   Dead letter queues for failed messages
-   Exactly-once delivery semantics

### Redis Requirements

You need to have Redis installed (version 5.0 or higher) and properly configured in your Laravel application.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [saravanasai](https://github.com/saravanasai)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
