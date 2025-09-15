Driver Architecture

The driver system is built around the EventStoreDriver interface, which defines the contract for all backend implementations:

```

<?php
interface EventStoreDriver
{
    public function publish(string $topic, array $payload): void;
    public function consume(string $topic, callable $callback, string $group): void;
    public function ack(string $topic, string $messageId, string $group): void;
    public function fail(string $topic, string $messageId, string $group): void;
    // Additional methods...
}

```

Driver Implementation
The primary driver is RedisStreamsDriver, which implements both EventStoreDriver and StreamUIInterface. The driver is registered in the service provider:

```
<?php
$this->app->singleton(EventStoreDriver::class, function () {
    $driver = config('stream-pulse.driver', 'redis');

    if ($driver === 'redis') {
        return new RedisStreamsDriver();
    }

    throw new \InvalidArgumentException("Unsupported driver: {$driver}");
});

```

Message Lifecycle Management
Pending Message Handling
The checkPendingMessages method handles messages that have been stuck in a pending state for too long:

```
<?php
protected function checkPendingMessages(string $topic, string $streamName, string $group): void
{
    $maxRetries = $this->getMaxRetries($topic);
    $minIdleTime = config(
        "stream-pulse.topics.{$topic}.min_idle_time",
        config('stream-pulse.defaults.min_idle_time', 30000)  // Default 30 seconds
    );

    $pendingMessages = $this->redis->xPending($streamName, $group);

    // Check each pending message
    foreach ($pendingDetails as $pending) {
        $messageId = $pending[0];
        $idleTimeMs = $pending[2];  // Idle time in milliseconds
        $deliveryCount = $pending[3];

        // Move to DLQ if retries exceeded and idle time criteria met
        if ($idleTimeMs >= $minIdleTime && $deliveryCount >= $maxRetries) {
            $this->fail($topic, $messageId, $group);
        }
    }
}

```

This method:

Gets the maximum retry count from configuration
Gets the minimum idle time from configuration (defaults to 30 seconds)
Retrieves pending messages for the consumer group
For each message, checks if it has been idle too long and exceeds retry count
Moves qualified messages to the Dead Letter Queue using the fail method

Stream Trimming
Stream trimming prevents unbounded growth of Redis streams:

```
<?php
protected function applyRetention(string $topic): void
{
    $streamName = $this->getStreamName($topic);
    $retention = $this->getRetention($topic);

    if ($retention > 0) {
        $this->redis->xTrim($streamName, 'MAXLEN', '~', $retention);
    }
}

protected function getRetention(string $topic): int
{
    return config(
        "stream-pulse.topics.{$topic}.retention",
        config('stream-pulse.defaults.retention', 1000)
    );
}

```

Trimming is:

Rate-limited (once every 5 minutes per topic) to avoid performance impacts
Configurable per topic with global defaults
Applied automatically after publishing events

Failed Message Handling
The fail method moves messages to a Dead Letter Queue:

```
<?php
public function fail(string $topic, string $messageId, string $group): void
{
    $dlqName = $this->getDLQ($topic);
    $streamName = $this->getStreamName($topic);
    $deadLetterStream = $this->getStreamName($dlqName);

    $pendingMessages = $this->redis->xPendingRange($streamName, $group, $messageId, $messageId, 1);

    if (!empty($pendingMessages)) {
        $message = $this->redis->xRange($streamName, $messageId, $messageId);

        if (!empty($message)) {
            // Add to DLQ and acknowledge original
            $this->redis->xAdd($deadLetterStream, '*', $message[$messageId]);
            $this->applyRetention($dlqName);
            $this->redis->xAck($streamName, $group, [$messageId]);
        }
    }
}

```

The fail process:

Determines the appropriate DLQ from configuration
Copies the message to the DLQ with its original payload
Acknowledges the original message to remove it from pending state
Applies retention policy to the DLQ

API Features

The package provides a clean and intuitive API:

Publishing Events

```
<?php
// Simple event publishing
StreamPulse::publish('orders', [
    'id' => 1234,
    'customer' => 'John Doe',
    'total' => 99.99
]);

```

Transaction-Aware Publishing

```
<?php
DB::transaction(function () {
    // Create order in database
    $order = Order::create([...]);

    // Only published if transaction succeeds
    StreamPulse::publishAfterCommit('orders', [
        'id' => $order->id,
        'status' => 'created'
    ]);
});

```

Consuming Events

```
<?php
// Basic consumption
StreamPulse::on('orders', function ($payload, $messageId) {
    // Process the order
    OrderProcessor::process($payload);
});

```

The driver system is designed to be extensible - the Redis implementation is the reference
Configuration follows a hierarchy: defaults → topic-specific
Dead Letter Queues (DLQs) are implemented as separate streams with the configured name
Failed message handling is automatic based on retry counts and idle time
Stream trimming is rate-limited to prevent performance impact
The package uses a transactional model for database-consistent event publishing
