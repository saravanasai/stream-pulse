# <img src="resources/images/logo.png" alt="StreamPulse Logo" width="200" height="100" style="vertical-align: middle;">

## Reliable Event Streaming for Laravel Applications

StreamPulse provides a seamless event streaming experience for Laravel developers. With a clean, intuitive API that feels native to the Laravel ecosystem, you can build robust, event-driven applications with minimal setup and configuration.

![StreamPulse Dashboard](resources/images/dashboard.png)

> **BETA VERSION** - StreamPulse is currently in beta. We'd love your feedback to help shape its future! Star the repo, open issues, or contribute to make StreamPulse even better.

## Why StreamPulse?

-   **Laravel-native experience** - API designed to feel natural to Laravel developers
-   **Simplified event streaming** - Complex Redis Streams concepts abstracted away
-   **Transaction awareness** - Events can be tied to database transactions
-   **Resilient processing** - Built-in support for retries and dead letter queues
-   **Real-time monitoring** - Beautiful dashboard for stream visualization and management

## Quick Start

### Installation

```bash
composer require saravanasai/stream-pulse
```

> **Note:** StreamPulse is designed for distributed systems where events can be published in one Laravel application and consumed in another. Events are persisted in Redis, allowing for communication between separate applications.

### Basic Usage

```php
// In your producer Laravel application
// Publish an event
StreamPulse::publish('orders', ['id' => 1234, 'amount' => 99.99]);

// In your consumer Laravel application
// Register an event handler - app service provider
StreamPulse::on('orders', function ($payload, $messageId) {
    OrderProcessor::process($payload);
});

// Run the consumer to process events (in the consumer app)
// php artisan streampulse:consume orders
```

## Key Features

-   **Simple, Laravel-style API** for publishing and consuming events
-   **Redis Streams integration** with plans for additional drivers
-   **Consumer group support** for distributed event processing
-   **Dead letter queue** for failed message handling
-   **Transaction-aware publishing** to ensure data consistency
-   **UI Dashboard** for monitoring streams and events

## Installation

You can install the package via composer:

```bash
composer require saravanasai/stream-pulse
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="stream-pulse-config"
```

You can also publish the views to customize the UI dashboard:

```bash
php artisan vendor:publish --tag="stream-pulse-views"
```

### Redis Requirements

You need Redis 5.0+ properly configured in your Laravel application.

## Usage Examples

### Publishing Events

StreamPulse provides a simple API for publishing events to any configured topic:

```php
use StreamPulse\StreamPulse\Facades\StreamPulse;

// Publish an event to a topic
StreamPulse::publish('orders', [
    'id' => 1234,
    'customer' => 'John Doe',
    'total' => 99.99,
    'items' => [
        ['product_id' => 101, 'quantity' => 2, 'price' => 49.99]
    ]
]);

// Publish after DB transaction commits
StreamPulse::publishAfterCommit('orders', $orderData);
```

### Transaction-Aware Event Publishing

Publish events only after a database transaction successfully commits:

```php
use Illuminate\Support\Facades\DB;
use StreamPulse\StreamPulse\Facades\StreamPulse;

DB::transaction(function () {
    // Create an order in the database
    $order = Order::create([
        'customer_id' => 123,
        'amount' => 99.99,
    ]);

    // This event will only be published if the transaction commits successfully
    StreamPulse::publishAfterCommit('orders', [
        'id' => $order->id,
        'status' => 'created',
        'customer_id' => $order->customer_id,
        'amount' => $order->amount,
    ]);

    // If the transaction fails or is rolled back, no event will be published
});
```

### Consuming Events with Handlers

The recommended way to consume events is to register handlers for your topics:

```php
// In a service provider
StreamPulse::on('orders', function ($payload, $messageId) {
    // Process the order
    OrderProcessor::process($payload);
});

// Then run the consumer command
// php artisan streampulse:consume orders
```

The consumer command will handle all the complexities of polling, acknowledgments, retries, and DLQ management for you.

### Stream Retention Management

StreamPulse automatically manages Redis streams to prevent unbounded growth:

```php
// Define global defaults in config/streampulse.php
'defaults' => [
    'retention' => 1000, // Keep 1000 events per stream by default
],

// Override for specific topics
'topics' => [
    'orders' => [
        'retention' => 5000, // Keep more events for important topics
    ],
    'logs' => [
        'retention' => 500, // Keep fewer events for high-volume topics
    ],
],
```

StreamPulse uses Redis's built-in XLEN and XTRIM commands to manage stream size. The retention value specifies the maximum number of messages that should be kept in each stream. When new messages are published, older messages exceeding this limit are automatically trimmed from the stream.

Key retention considerations:

-   **Higher retention values** provide longer message history but consume more memory
-   **Lower retention values** minimize memory usage but reduce how far back you can access messages
-   **Topic-specific overrides** allow you to balance memory usage based on each stream's importance
-   **Automatic trimming** occurs during publish operations to maintain the configured limits
-   **Scheduled trimming** is also performed via Laravel's scheduler to ensure streams remain within size limits even during periods of low publishing activity. In local environments, make sure to run `php artisan schedule:run` command to enable this feature.

## Configuration

The published configuration file includes several sections to customize StreamPulse's behavior:

```php
return [
    /*
    | Default Driver
    | Available: "redis", "nats" (NATS support coming in future releases)
    */
    'driver' => env('STREAMPULSE_DRIVER', 'redis'),

    /*
    | Strict Mode - When enabled, only explicitly defined topics can be used
    */
    'strict_mode' => env('STREAMPULSE_STRICT_MODE', true),

    /*
    | Auto Processing - Automatically process pending messages that exceed retry limits
    */
    'auto_process_pending' => env('STREAMPULSE_AUTO_PROCESS', true),

    /*
    | Global Defaults - Applied to all topics unless overridden
    */
    'defaults' => [
        'max_retries' => 3,
        'dlq' => 'dead_letter',
        'retention' => 1000,
        'min_idle_time' => 30000,  // Minimum time (ms) before re-processing pending messages
        'preserve_order' => false, // Whether to enforce strict message ordering
    ],

    /*
    | Topics - Per-topic configuration overrides
    */
    'topics' => [
        'orders' => [
            'max_retries' => 5,
            'dlq' => 'orders_dlq',
            'retention' => 5000,
            'min_idle_time' => 60000,
            'preserve_order' => true,
        ],
        // Other topics...
    ],

    /*
    | Drivers - Backend-specific configuration
    */
    'drivers' => [
        'redis' => [
            'connection' => env('REDIS_CONNECTION', 'default'),
            'stream_prefix' => 'streampulse:',
        ],
    ],

    /*
    | UI Settings - Dashboard configuration
    */
    'ui' => [
        'enabled' => env('STREAMPULSE_UI_ENABLED', true),
        'page_size' => env('STREAMPULSE_UI_PAGE_SIZE', 50),
        'route_prefix' => 'stream-pulse',
    ],
];
```

### Key Configuration Options

-   **Default Driver**: Currently Redis Streams is the only implemented driver, with NATS planned for future releases.
-   **Strict Mode**: Prevents accidental topic creation in production by limiting to only explicitly defined topics.
-   **Auto Processing**: Automatically schedules a task to process pending messages and move them to DLQ after exceeding retry limits.
-   **Global Defaults**:
    -   `max_retries`: Number of retry attempts before moving to DLQ
    -   `dlq`: Dead letter queue name for failed messages
    -   `retention`: Maximum number of messages to retain per stream
    -   `min_idle_time`: Minimum time in milliseconds before re-processing pending messages
    -   `preserve_order`: Whether to enforce strict message ordering
-   **Topics**: Configure specific topics with custom settings that override defaults
-   **Drivers**: Backend-specific settings (currently Redis)
-   **UI Settings**: Configure the included web dashboard

## Advanced Usage

### Low-level Consumption API

For more control, you can use the low-level consumption API:

```php
use StreamPulse\StreamPulse\Facades\StreamPulse;

// Consume events from a topic with a consumer group
StreamPulse::consume('orders', 'order-processors', function ($payload, $messageId) {
    try {
        // Process the event
        OrderProcessor::process($payload);

        // Acknowledge the message as processed
        StreamPulse::ack('orders', $messageId, 'order-processors');
    } catch (\Exception $e) {
        // Handle error
        // The message will remain in pending state and can be retried
        // After max retries, it will be moved to the DLQ automatically
    }
});
```

## UI Dashboard

StreamPulse includes a web dashboard for monitoring and inspecting your streams and events:

### Dashboard Features

-   View all available streams/topics
-   Browse events by topic with pagination - coming soon
-   Examine event details including payload and metadata - coming soon
-   Track failed events across all topics - coming soon
-   Real-time monitoring of stream activity - coming soon
-   Visual analytics of event processing - coming soon

### Accessing the Dashboard

The dashboard is available at the route `/stream-pulse` and is protected by the `web` and `auth` middleware by default.

Routes include:

-   Dashboard: `/stream-pulse`
-   Topic Events: `/stream-pulse/topics/{topic}`

## Architecture

StreamPulse uses Redis Streams as the default driver, which provides:

-   Persistent message storage
-   Consumer groups for distributed processing
-   Automatic tracking of processed messages
-   Dead letter queues for failed messages
-   Exactly-once delivery semantics

The architecture is designed with a driver-based approach, allowing for seamless integration of new streaming technologies as they become available.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
