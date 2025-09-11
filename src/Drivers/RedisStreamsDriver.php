<?php

namespace StreamPulse\StreamPulse\Drivers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;
use StreamPulse\StreamPulse\Contracts\StreamUIInterface;
use StreamPulse\StreamPulse\StreamPulse;

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
     * Get stream retention setting for a topic
     */
    protected function getRetention(string $topic): int
    {
        return app(StreamPulse::class)->getRetention($topic);
    }

    /**
     * Apply retention policy to a stream
     */
    protected function applyRetention(string $topic): void
    {
        $streamName = $this->getStreamName($topic);
        $retention = $this->getRetention($topic);

        if ($retention > 0) {
            // Use approximate trimming for better performance
            $this->redis->xTrim($streamName, 'MAXLEN', '~', $retention);
        }
    }

    /**
     * Get max retries for a topic
     */
    protected function getMaxRetries(string $topic): int
    {
        return app(StreamPulse::class)->getMaxRetries($topic);
    }

    /**
     * Get DLQ for a topic
     */
    protected function getDLQ(string $topic): string
    {
        return app(StreamPulse::class)->getDLQ($topic);
    }

    /**
     * Manually trim a stream to a specific length
     */
    public function trimStream(string $topic, int $length): void
    {
        $streamName = $this->getStreamName($topic);

        // Check if stream exists
        if (!$this->redis->exists($streamName)) {
            return;
        }

        // Apply precise trim to exact length
        $this->redis->xTrim($streamName, 'MAXLEN', $length);

        // Also check for a failed stream and trim it
        $failedStreamName = $this->getStreamName($this->getDLQ($topic));
        if ($this->redis->exists($failedStreamName)) {
            $this->redis->xTrim($failedStreamName, 'MAXLEN', $length);
        }
    }

    /**
     * Publish an event to a topic.
     */
    public function publish(string $topic, array $payload): void
    {
        // Validate topic according to strict mode rules
        app(StreamPulse::class)->validateTopic($topic);

        $streamName = $this->getStreamName($topic);

        // Format payload for Redis Streams (flat key-value pairs)
        $formattedPayload = [];
        foreach ($payload as $key => $value) {
            $formattedPayload[$key] = is_array($value) || is_object($value)
                ? json_encode($value)
                : (string) $value;
        }

        $this->redis->xAdd($streamName, '*', $formattedPayload);

        // Apply retention policy after publishing
        $this->applyRetention($topic);
    }

    /**
     * Check pending messages for max retries
     */
    protected function checkPendingMessages(string $topic, string $streamName, string $group): void
    {
        $maxRetries = $this->getMaxRetries($topic);

        // Get summary of pending messages
        $pendingMessages = $this->redis->xPending($streamName, $group);

        // Check if there are any messages that have been pending for too long
        if (!empty($pendingMessages) && isset($pendingMessages['pending']) && $pendingMessages['pending'] > 0) {
            // Get details about pending messages
            $pendingDetails = $this->redis->xPendingRange($streamName, $group, '-', '+', 10);

            foreach ($pendingDetails as $pending) {
                $messageId = $pending[0];
                $deliveryCount = $pending[3];

                // If message has been delivered too many times, move to DLQ
                if ($deliveryCount >= $maxRetries) {
                    $this->fail($topic, $messageId, $group);
                    Log::warning("Message {$messageId} in topic {$topic} exceeded max retries ({$maxRetries}) and was moved to DLQ");
                }
            }
        }
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

        // Check and process pending messages that exceeded max retries
        $this->checkPendingMessages($topic, $streamName, $group);

        // Read new messages from the stream
        $newMessages = $this->redis->xReadGroup(
            $group,
            $consumerName,
            [$streamName => '>'],
            10, // Count
            1000   // Block for 1 second
        );

        if ($newMessages) {
            foreach ($newMessages as $messages) {
                foreach ($messages as $messageId => $payload) {
                    try {
                        $callback($payload, $messageId);
                        // Auto-acknowledge on successful processing
                        $this->ack($topic, $messageId, $group);
                    } catch (\Exception $e) {
                        Log::error("Error processing message {$messageId} from {$topic}: " . $e->getMessage());
                        // Message will remain pending and can be retried
                    }
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
        // Get the configured dead letter queue
        $dlqName = $this->getDLQ($topic);

        $streamName = $this->getStreamName($topic);
        $deadLetterStream = $this->getStreamName($dlqName);

        // Get the message from the pending list
        $pendingMessages = $this->redis->xPendingRange($streamName, $group, $messageId, $messageId, 1);

        if (! empty($pendingMessages)) {
            // Get the actual message content
            $message = $this->redis->xRange($streamName, $messageId, $messageId);

            if (! empty($message)) {
                // Move to dead letter queue
                $this->redis->xAdd($deadLetterStream, '*', $message[$messageId]);

                // Apply retention policy to the DLQ too
                $this->applyRetention($dlqName);

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
        $laravelPrefix = config('database.redis.options.prefix', '');

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
        $failedEvents = [];

        // Get all configured DLQs from topics
        $dlqs = [];
        $topics = $this->listTopics();

        // Add the default DLQ
        $dlqs[] = config('streampulse.defaults.dlq', 'dead_letter');

        // Add topic-specific DLQs
        foreach ($topics as $topic) {
            $topicConfig = config("streampulse.topics.{$topic}", []);
            if (isset($topicConfig['dlq'])) {
                $dlqs[] = $topicConfig['dlq'];
            }
        }

        // Make the list unique
        $dlqs = array_unique($dlqs);

        // Fetch events from all DLQs
        foreach ($dlqs as $dlq) {
            $dlqStreamName = $this->getStreamName($dlq);

            // Check if this DLQ exists
            if ($this->redis->exists($dlqStreamName)) {
                $events = $this->redis->xRange($dlqStreamName, '-', '+', 100);

                foreach ($events as $eventId => $payload) {
                    // Try to determine original topic from the payload if available
                    $originalTopic = $payload['original_topic'] ?? $dlq;

                    $failedEvents[] = [
                        'topic' => $originalTopic,
                        'dlq' => $dlq,
                        'event_id' => $eventId,
                        'payload' => $payload,
                        'timestamp' => $this->getTimestampFromId($eventId),
                    ];
                }
            }
        }

        // Also check for legacy failed streams with the FAILED_SUFFIX
        $pattern = $this->prefix . '*' . self::FAILED_SUFFIX;
        $keys = $this->redis->keys($pattern);

        foreach ($keys as $failedStreamKey) {
            $topic = str_replace([$this->prefix, self::FAILED_SUFFIX], '', $failedStreamKey);
            $events = $this->redis->xRange($failedStreamKey, '-', '+', 100);

            foreach ($events as $eventId => $payload) {
                $failedEvents[] = [
                    'topic' => $topic,
                    'dlq' => $topic . self::FAILED_SUFFIX,
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
