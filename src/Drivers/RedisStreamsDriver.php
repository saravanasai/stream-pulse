<?php

namespace StreamPulse\StreamPulse\Drivers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;
use StreamPulse\StreamPulse\Contracts\StreamUIInterface;

class RedisStreamsDriver implements EventStoreDriver, StreamUIInterface
{
    /**
     * Suffix for failed events streams.
     */
    private const FAILED_SUFFIX = ':failed';

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
        return $this->prefix . $topic;
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
        $consumerName = gethostname() . ':' . getmypid();

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
        $deadLetterStream = $this->getStreamName($topic . self::FAILED_SUFFIX);

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

    /**
     * List all available topics/streams.
     */
    public function listTopics(): array
    {
        $pattern = $this->prefix . '*';
        $keys = $this->redis->keys($pattern);
        Log::info('Redis keys found: ' . implode(', ', $keys));
        $topics = [];

        // Get Laravel's default Redis prefix using config for runtime flexibility
        $laravelPrefix =  config('database.redis.options.prefix', '');

        foreach ($keys as $key) {
            // Skip the failed topics
            if (! str_contains($key, self::FAILED_SUFFIX)) {
                // Remove both Laravel and package prefixes
                $topic = $key;
                if (str_starts_with($topic, $laravelPrefix)) {
                    $topic = substr($topic, strlen($laravelPrefix));
                }
                if (str_starts_with($topic, $this->prefix)) {
                    $topic = substr($topic, strlen($this->prefix));
                }
                $topics[] = $topic;
            }
        }

        return $topics;
    }

    /**
     * List failed or dead-lettered events.
     */
    public function listFailedEvents(): array
    {
        $pattern = $this->prefix . '*' . self::FAILED_SUFFIX;
        $keys = $this->redis->keys($pattern);
        $failedEvents = [];

        foreach ($keys as $failedStreamKey) {
            $topic = str_replace([$this->prefix, self::FAILED_SUFFIX], '', $failedStreamKey);
            $events = $this->redis->xRange($failedStreamKey, '-', '+', 100);

            foreach ($events as $eventId => $payload) {
                $failedEvents[] = [
                    'topic' => $topic,
                    'event_id' => $eventId,
                    'payload' => $payload,
                    'timestamp' => $this->getTimestampFromId($eventId),
                ];
            }
        }

        // Sort by timestamp (newest first)
        usort($failedEvents, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $failedEvents;
    }

    /**
     * Get recent events for a specific topic.
     */
    public function getEventsByTopic(string $topic, int $limit = 50, int $offset = 0): array
    {
        $streamName = $this->getStreamName($topic);
        $events = [];

        // Get all events from newest to oldest
        $streamEvents = $this->redis->xRevRange($streamName, '+', '-', $limit + $offset);

        // Apply offset
        $streamEvents = array_slice($streamEvents, $offset, $limit);

        foreach ($streamEvents as $eventId => $payload) {
            $events[] = [
                'event_id' => $eventId,
                'payload' => $payload,
                'timestamp' => $this->getTimestampFromId($eventId),
            ];
        }

        return $events;
    }

    /**
     * Get detailed payload and metadata of a single event.
     */
    public function getEventDetails(string $topic, string $eventId): array
    {
        $streamName = $this->getStreamName($topic);
        $events = $this->redis->xRange($streamName, $eventId, $eventId);

        if (empty($events)) {
            // Check if it's in the failed events
            $failedStreamName = $this->getStreamName($topic . self::FAILED_SUFFIX);
            $events = $this->redis->xRange($failedStreamName, $eventId, $eventId);

            if (empty($events)) {
                return []; // Event not found
            }
        }

        $payload = reset($events);

        return [
            'event_id' => $eventId,
            'topic' => $topic,
            'payload' => $payload,
            'timestamp' => $this->getTimestampFromId($eventId),
            'is_failed' => empty($this->redis->xRange($streamName, $eventId, $eventId)),
        ];
    }

    /**
     * Extract timestamp from a Redis stream ID.
     */
    protected function getTimestampFromId(string $id): int
    {
        // Redis stream IDs are in the format: timestamp-sequence
        $parts = explode('-', $id);

        return (int) $parts[0];
    }
}
