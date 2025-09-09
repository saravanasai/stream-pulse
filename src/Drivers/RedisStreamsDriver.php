<?php

namespace StreamPulse\StreamPulse\Drivers;

use Illuminate\Support\Facades\Redis;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;

class RedisStreamsDriver implements EventStoreDriver
{
    /**
     * Redis connection.
     *
     * @var mixed
     */
    protected $redis;

    /**
     * Stream prefix.
     */
    protected string $prefix;

    /**
     * Create a new Redis Streams driver instance.
     *
     * @return void
     */
    public function __construct()
    {
        $connectionName = config('streampulse.drivers.redis.connection', 'default');
        $this->redis = Redis::connection($connectionName)->client();
        $this->prefix = config('streampulse.drivers.redis.stream_prefix', 'streampulse:');
    }

    /**
     * Get the full stream name with prefix.
     */
    protected function getStreamName(string $topic): string
    {
        return $this->prefix.$topic;
    }

    /**
     * Publish an event to a topic.
     */
    public function publish(string $topic, array $payload): void
    {
        $streamName = $this->getStreamName($topic);

        // Format payload for Redis Streams (flat key-value pairs)
        $formattedPayload = [];
        foreach ($payload as $key => $value) {
            $formattedPayload[$key] = is_array($value) || is_object($value)
                ? json_encode($value)
                : (string) $value;
        }

        $this->redis->xAdd($streamName, '*', $formattedPayload);
    }

    /**
     * Consume events from a topic.
     */
    public function consume(string $topic, callable $callback, string $group): void
    {
        $streamName = $this->getStreamName($topic);
        $consumerName = gethostname().':'.getmypid();

        // Create consumer group if it doesn't exist
        try {
            $this->redis->xGroup('CREATE', $streamName, $group, '0', true);
        } catch (\Exception $e) {
            // Group already exists, continue
        }

        // Read from stream (pending messages first, then new ones)
        $pendingMessages = $this->redis->xReadGroup(
            $group,
            $consumerName,
            [$streamName => '>'],
            1, // Count
            0   // No block
        );

        if ($pendingMessages) {
            foreach ($pendingMessages as $messages) {
                foreach ($messages as $messageId => $payload) {
                    $callback($payload, $messageId);
                }
            }
        }
    }

    /**
     * Acknowledge a message as processed.
     */
    public function ack(string $topic, string $messageId, string $group): void
    {
        $streamName = $this->getStreamName($topic);
        $this->redis->xAck($streamName, $group, [$messageId]);
    }

    /**
     * Mark a message as failed.
     */
    public function fail(string $topic, string $messageId, string $group): void
    {
        // In Redis Streams, failing is just not acknowledging,
        // but we could implement additional logic here like moving to a dead letter queue
        $streamName = $this->getStreamName($topic);
        $deadLetterStream = $this->getStreamName($topic.':failed');

        // Get the message from the pending list
        $pendingMessages = $this->redis->xPendingRange($streamName, $group, $messageId, $messageId, 1);

        if (! empty($pendingMessages)) {
            // Get the actual message content
            $message = $this->redis->xRange($streamName, $messageId, $messageId);

            if (! empty($message)) {
                // Move to dead letter queue
                $this->redis->xAdd($deadLetterStream, '*', $message[$messageId]);

                // Acknowledge the original message
                $this->redis->xAck($streamName, $group, [$messageId]);
            }
        }
    }
}
