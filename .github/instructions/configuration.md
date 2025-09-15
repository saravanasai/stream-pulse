Configuration System
The configuration file (stream-pulse.php) provides a hierarchical structure:

1. Driver Selection

```
<?php
'driver' => env('STREAMPULSE_DRIVER', 'redis'),

```

Determines which backend driver to use, currently supporting Redis with planned NATS support.

2. Strict Mode

```
<?php
'strict_mode' => env('STREAMPULSE_STRICT_MODE', true),

```

When enabled, only pre-defined topics can be used, preventing accidental topic creation.

3. Global Defaults

```
<?php
'defaults' => [
    'max_retries' => 3,         // Default retry attempts before DLQ
    'dlq' => 'dead_letter',     // Default DLQ name
    'retention' => 1000,        // Default stream length
    'min_idle_time' => 30000,   // Default min idle time (30 seconds)
],

```

Defines default values used when topic-specific settings are not provided.

```
<?php
'topics' => [
    'orders' => [
        'max_retries' => 5,       // Higher retry count for important events
        'dlq' => 'orders_dlq',    // Custom DLQ for orders
        'retention' => 5000,      // Keep more order events
        'min_idle_time' => 60000, // Wait longer before considering idle (60 seconds)
    ],
],

```

Override defaults for specific topics based on their importance and requirements.

Driver-Specific Settings

```
<?php
'drivers' => [
   'redis' => [
       'connection' => env('REDIS_CONNECTION', 'default'),
       'stream_prefix' => 'streampulse:',
   ],
],

```

Configure backend-specific parameters like connection details and namespace prefixes.

6. UI Settings

Controls the dashboard UI behavior, pagination, and routing.

```
<?php
'ui' => [
    'enabled' => env('STREAMPULSE_UI_ENABLED', true),
    'page_size' => env('STREAMPULSE_UI_PAGE_SIZE', 50),
    'route_prefix' => 'stream-pulse',
],

```

Configuration Usage
The configuration values are accessed throughout the package:

Driver Selection: Used in StreamPulseServiceProvider to determine which driver to instantiate
Strict Mode: Used in StreamPulse::validateTopic() to enforce topic validation
Retry Settings: Used in checkPendingMessages() to determine when to move to DLQ
Retention: Used in applyRetention() to trim streams to manageable size
DLQ Names: Used in fail() to determine where to move failed messages
Integration with Laravel
The package integrates with Laravel through:

Service Provider: StreamPulseServiceProvider bootstraps the package
Facade: StreamPulse facade provides static API access
Commands: Artisan commands for maintenance and consumers
Transaction Events: DB transaction hooks for reliable event publishing
Controllers: HTTP controllers for the dashboard UI
