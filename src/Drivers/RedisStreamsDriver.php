<?php

namespace StreamPulse\StreamPulse\Drivers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;
use StreamPulse\StreamPulse\Contracts\StreamUIInterface;

/**
 * Redis Streams Driver for StreamPulse
 *
 * This driver implements the EventStoreDriver and StreamUIInterface contracts
 * using Redis Streams as the underlying storage mechanism. It provides language-agnostic
 * serialization and deserialization of payloads, making it suitable for cross-language
 * event processing systems.
 *
 * Events are published with type metadata to preserve data types like arrays, booleans,
 * integers, and floats. This allows for proper reconstruction of these types when consumed,
 * regardless of the consumer's programming language.
 */
class RedisStreamsDriver implements EventStoreDriver, StreamUIInterface
{
    /**
     * Redis client instance.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redis;

    /**
     * Stream prefix without Redis database prefix.
     */
    protected string $prefix;

    /**
     * Full stream prefix including Redis database prefix.
     */
    protected string $fullPrefix;

    /**
     * Type prefixes used for payload serialization.
     * These constants help identify the original data type when hydrating the payload.
     */
    private const PREFIX_JSON = '__json:';

    private const PREFIX_BOOL = '__bool:';

    private const PREFIX_NULL = '__null';

    private const PREFIX_INT = '__int:';

    private const PREFIX_FLOAT = '__float:';

    public function __construct()
    {
        $connectionName = config('stream-pulse.drivers.redis.connection', 'default');
        $this->redis = Redis::connection($connectionName)->client();
        $redisPrefix = config('database.redis.options.prefix');
        $streamPrefix = config('stream-pulse.drivers.redis.stream_prefix', 'stream-pulse:');

        $this->prefix = $streamPrefix;
        $this->fullPrefix = (string) $redisPrefix.$streamPrefix;
    }

    /**
     * Get the Redis stream name for a topic.
     *
     * @param  string  $topic  The topic name
     * @return string The formatted stream name
     */
    public function getStreamName(string $topic): string
    {
        return $this->prefix.$topic;
    }

    /**
     * Get the maximum number of retries for a topic.
     *
     * @param  string  $topic  The topic name
     * @return int The maximum number of retries
     */
    public function getMaxRetries(string $topic): int
    {
        return config(
            "stream-pulse.topics.{$topic}.max_retries",
            config('stream-pulse.defaults.max_retries', 3)
        );
    }

    /**
     * Get the retention setting for a topic.
     *
     * @param  string  $topic  The topic name
     * @return int The retention setting
     */
    public function getRetention(string $topic): int
    {
        return config(
            "stream-pulse.topics.{$topic}.retention",
            config('stream-pulse.defaults.retention', 1000)
        );
    }

    /**
     * Apply retention policy to a stream
     * Can be called directly or via scheduled command
     */
    public function applyRetention(string $topic): void
    {
        $startTime = microtime(true);
        $streamName = $this->getStreamName($topic);
        $retention = $this->getRetention($topic);

        // Dispatch event before applying retention
        Event::dispatch('stream-pulse.retention-applying', [
            'topic' => $topic,
            'retention' => $retention,
        ]);

        if ($retention > 0) {
            $trimmed = $this->redis->xTrim($streamName, $retention);
            // Dispatch event after applying retention
            Event::dispatch('stream-pulse.retention-applied', [
                'topic' => $topic,
                'retention' => $retention,
                'trimmed_count' => $trimmed,
                'duration' => microtime(true) - $startTime,
            ]);
        }
    }

    /**
     * Get the Dead Letter Queue (DLQ) for a topic.
     *
     * @param  string  $topic  The topic name
     * @return string The DLQ name
     */
    public function getDLQ(string $topic): string
    {
        $dlq = config("stream-pulse.topics.{$topic}.dlq");
        if (is_null($dlq)) {
            $dlq = config('stream-pulse.defaults.dlq');
        }

        return $dlq;
    }

    /**
     * Publish an event to a Redis stream.
     *
     * This method serializes the payload in a language-agnostic way to ensure
     * compatibility with consumers written in different programming languages.
     * Complex types (arrays, objects) are JSON encoded with a type metadata prefix.
     *
     * @param  string  $topic  The topic name
     * @param  array  $payload  The event payload
     * @param  array  $config  Additional configuration options
     */
    public function publish(string $topic, array $payload, array $config): void
    {
        $streamName = $this->getStreamName($topic);

        // Dispatch event before publishing
        Event::dispatch('stream-pulse.publishing', [$topic, $payload, $config]);

        $startTime = microtime(true);

        $formattedPayload = [];
        foreach ($payload as $key => $value) {
            if (is_array($value) || is_object($value)) {
                // Convert all complex types to JSON for cross-language compatibility
                $formattedPayload[$key] = self::PREFIX_JSON.json_encode($value);
            } elseif (is_bool($value)) {
                $formattedPayload[$key] = self::PREFIX_BOOL.($value ? 'true' : 'false');
            } elseif (is_null($value)) {
                $formattedPayload[$key] = self::PREFIX_NULL;
            } elseif (is_int($value)) {
                $formattedPayload[$key] = self::PREFIX_INT.(string) $value;
            } elseif (is_float($value)) {
                $formattedPayload[$key] = self::PREFIX_FLOAT.(string) $value;
            } else {
                $formattedPayload[$key] = (string) $value;
            }
        }

        $messageId = $this->redis->xAdd($streamName, '*', $formattedPayload);

        $duration = microtime(true) - $startTime;

        // Dispatch event after publishing
        Event::dispatch('stream-pulse.published', [
            'topic' => $topic,
            'message_id' => $messageId,
            'duration' => $duration,
            'size' => strlen(json_encode($formattedPayload)),
        ]);
    }

    /**
     * Check for messages that have been pending too long and may need to be moved to DLQ
     * Only called when rate limiting allows
     */
    public function checkPendingMessages(string $topic, string $streamName, string $group): void
    {
        $maxRetries = $this->getMaxRetries($topic);
        $pendingMessages = $this->redis->xPending($streamName, $group);

        $minIdleTime = config(
            "stream-pulse.topics.{$topic}.min_idle_time",
            config('stream-pulse.defaults.min_idle_time', 30000)  // Default 30 seconds
        );

        if (! empty($pendingMessages) && isset($pendingMessages['pending']) && $pendingMessages['pending'] > 0) {
            // Get pending messages with their idle time - limit to 20 at a time for efficiency
            $pendingDetails = $this->redis->xPendingRange($streamName, $group, '-', '+', 20);

            foreach ($pendingDetails as $pending) {
                $messageId = $pending[0];
                $idleTimeMs = $pending[2];  // Idle time in milliseconds
                $deliveryCount = $pending[3];

                // Only consider messages that have been idle for longer than the minimum time
                // AND have exceeded the maximum retry count
                if ($idleTimeMs >= $minIdleTime && $deliveryCount >= $maxRetries) {
                    $this->fail($topic, $messageId, $group);
                }
            }
        }
    }

    /**
     * Consume messages from a Redis stream.
     *
     * This method reads messages from a Redis stream, hydrates the payload,
     * and passes it to the provided callback function.
     *
     * For topics with 'preserve_order' set to true, this method guarantees
     * messages will be processed in strict chronological order by:
     * 1. Using a consistent consumer name for all instances
     * 2. Processing one message at a time
     *
     * @param  string  $topic  The topic name to consume from
     * @param  callable  $callback  The callback function to process the message
     * @param  string  $group  The consumer group name
     */
    public function consume(string $topic, callable $callback, string $group): void
    {
        $streamName = $this->getStreamName($topic);
        $requireOrdering = config(
            "stream-pulse.topics.{$topic}.preserve_order",
            config('stream-pulse.defaults.preserve_order', false)
        );

        // For ordered topics, use a static consumer name to ensure all messages
        // for this topic/group combination go to the same consumer
        $consumerName = $requireOrdering
            ? "ordered-consumer-{$topic}-{$group}"
            : gethostname().':'.getmypid();

        // Dispatch event before consuming
        Event::dispatch('stream-pulse.consuming', [
            'topic' => $topic,
            'group' => $group,
            'consumer' => $consumerName,
            'ordered' => $requireOrdering,
        ]);

        $startTime = microtime(true);
        $messageCount = 0;
        $errorCount = 0;

        try {
            $this->redis->xGroup('CREATE', $streamName, $group, '0', true);
        } catch (\Exception $e) {
            // Group already exists, continue
        }

        // When ordering is required, process one message at a time
        $batchSize = $requireOrdering ? 1 : 100;

        $newMessages = $this->redis->xReadGroup(
            $group,
            $consumerName,
            [$streamName => '>'],
            $batchSize,
            1000
        );

        if ($newMessages) {
            foreach ($newMessages as $messages) {
                // Sort messages by ID when ordering is required
                if ($requireOrdering) {
                    ksort($messages);
                }

                foreach ($messages as $messageId => $payload) {
                    $processingStartTime = microtime(true);
                    try {
                        // Hydrate the payload before passing to callback
                        $hydratedPayload = $this->hydrate($payload);

                        // Dispatch event before processing message
                        Event::dispatch('stream-pulse.message-processing', [
                            'topic' => $topic,
                            'message_id' => $messageId,
                            'group' => $group,
                            'ordered' => $requireOrdering,
                        ]);

                        $callback($hydratedPayload, $messageId);
                        $this->ack($topic, $messageId, $group);
                        $messageCount++;

                        // Dispatch event after message processed
                        Event::dispatch('stream-pulse.message-processed', [
                            'topic' => $topic,
                            'message_id' => $messageId,
                            'duration' => microtime(true) - $processingStartTime,
                            'success' => true,
                            'ordered' => $requireOrdering,
                        ]);
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error("Error processing message {$messageId} from {$topic}: ".$e->getMessage());

                        // Dispatch event for failed message
                        Event::dispatch('stream-pulse.message-failed', [
                            'topic' => $topic,
                            'message_id' => $messageId,
                            'error' => $e->getMessage(),
                            'duration' => microtime(true) - $processingStartTime,
                            'ordered' => $requireOrdering,
                        ]);

                        // When ordering is required, stop processing on first error
                        // to prevent out-of-order processing
                        if ($requireOrdering) {
                            break;
                        }
                    }
                }

                // If ordering required and we had an error, stop processing this batch
                if ($requireOrdering && $errorCount > 0) {
                    break;
                }
            }
        }

        $duration = microtime(true) - $startTime;

        // Dispatch event after consuming batch
        Event::dispatch('stream-pulse.consumed', [
            'topic' => $topic,
            'group' => $group,
            'duration' => $duration,
            'message_count' => $messageCount,
            'error_count' => $errorCount,
            'ordered' => $requireOrdering,
            'success' => $errorCount === 0,
        ]);
    }

    /**
     * Hydrate a message payload from its serialized form.
     *
     * This method converts the serialized Redis stream data back to its original
     * PHP data types based on the type metadata prefix added during serialization.
     * It's designed to work with data produced by non-PHP producers as well.
     *
     * @param  array  $payload  The raw message payload from Redis
     * @return array The hydrated payload with proper PHP data types
     */
    protected function hydrate(array $payload): array
    {
        $hydratedPayload = [];

        foreach ($payload as $key => $value) {
            // Skip non-string values (should not happen in Redis streams, but just in case)
            if (! is_string($value)) {
                $hydratedPayload[$key] = $value;

                continue;
            }

            // Handle different data type prefixes
            if (strpos($value, self::PREFIX_JSON) === 0) {
                $jsonData = substr($value, strlen(self::PREFIX_JSON));
                $hydratedPayload[$key] = json_decode($jsonData, true);
            } elseif (strpos($value, self::PREFIX_BOOL) === 0) {
                $hydratedPayload[$key] = substr($value, strlen(self::PREFIX_BOOL)) === 'true';
            } elseif ($value === self::PREFIX_NULL) {
                $hydratedPayload[$key] = null;
            } elseif (strpos($value, self::PREFIX_INT) === 0) {
                $hydratedPayload[$key] = (int) substr($value, strlen(self::PREFIX_INT));
            } elseif (strpos($value, self::PREFIX_FLOAT) === 0) {
                $hydratedPayload[$key] = (float) substr($value, strlen(self::PREFIX_FLOAT));
            } else {
                $hydratedPayload[$key] = $value;
            }
        }

        return $hydratedPayload;
    }

    public function ack(string $topic, string $messageId, string $group): void
    {
        $startTime = microtime(true);

        // Dispatch event before acknowledging message
        Event::dispatch('stream-pulse.acknowledging', [
            'topic' => $topic,
            'message_id' => $messageId,
            'group' => $group,
        ]);

        $streamName = $this->getStreamName($topic);
        $this->redis->xAck($streamName, $group, [$messageId]);

        // Dispatch event after acknowledging message
        Event::dispatch('stream-pulse.acknowledged', [
            'topic' => $topic,
            'message_id' => $messageId,
            'group' => $group,
            'duration' => microtime(true) - $startTime,
        ]);
    }

    public function fail(string $topic, string $messageId, string $group): void
    {
        $startTime = microtime(true);

        // Dispatch event before failing message
        Event::dispatch('stream-pulse.failing', [
            'topic' => $topic,
            'message_id' => $messageId,
            'group' => $group,
        ]);

        $dlqName = $this->getDLQ($topic);
        $streamName = $this->getStreamName($topic);
        $deadLetterStream = $this->getStreamName($dlqName);

        $pendingMessages = $this->redis->xPendingRange($streamName, $group, $messageId, $messageId, 1);

        if (! empty($pendingMessages)) {
            $message = $this->redis->xRange($streamName, $messageId, $messageId);

            if (! empty($message)) {
                $this->redis->xAdd($deadLetterStream, '*', $message[$messageId]);
                $this->redis->xAck($streamName, $group, [$messageId]);

                // Dispatch event after failing message
                Event::dispatch('stream-pulse.failed', [
                    'topic' => $topic,
                    'message_id' => $messageId,
                    'dlq' => $dlqName,
                    'duration' => microtime(true) - $startTime,
                    'success' => true,
                ]);
            } else {
                // Dispatch event for message not found
                Event::dispatch('stream-pulse.failed', [
                    'topic' => $topic,
                    'message_id' => $messageId,
                    'dlq' => $dlqName,
                    'duration' => microtime(true) - $startTime,
                    'success' => false,
                    'reason' => 'Message not found in stream',
                ]);
            }
        } else {
            // Dispatch event for message not pending
            Event::dispatch('stream-pulse.failed', [
                'topic' => $topic,
                'message_id' => $messageId,
                'dlq' => $dlqName,
                'duration' => microtime(true) - $startTime,
                'success' => false,
                'reason' => 'Message not pending',
            ]);
        }
    }

    /**
     * List all available topics/streams.
     *
     * Uses Redis scan command to retrieve all stream keys and extracts topic names
     * from the full key pattern.
     *
     * @return array Array of topic names
     */
    public function listTopics(): array
    {
        // Pattern to match all streams with our prefix
        $pattern = $this->fullPrefix.'*';
        $keys = [];

        // Use scan command to reliably fetch keys matching our pattern
        $iterator = null;
        do {
            $result = $this->redis->scan($iterator, $pattern, 100);
            if ($result) {
                $keys = array_merge($keys, $result);
            }
        } while ($iterator !== 0);

        // If scan failed or returned no results, fall back to keys command
        if (empty($keys)) {
            $keys = $this->redis->keys($pattern);
        }

        // Extract topic names from the full key names
        $topics = [];
        foreach ($keys as $key) {
            $topic = str_replace($this->fullPrefix, '', $key);
            $topics[] = $topic;
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

        // Add the default DLQ
        $dlqs[] = config('stream-pulse.defaults.dlq');

        // Add topic-specific DLQs
        $topicConfigs = config('stream-pulse.topics', []);
        foreach ($topicConfigs as $config) {
            if (isset($config['dlq'])) {
                $dlqs[] = $config['dlq'];
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
                        'is_failed' => true,
                    ];
                }
            }
        }

        return $failedEvents;
    }

    /**
     * Get recent events for a specific topic.
     */
    public function getEventsByTopic(string $topic, int $limit = 50, int $offset = 0): array
    {
        $streamName = $this->getStreamName($topic);
        $events = [];

        // Check if stream exists
        if (! $this->redis->exists($streamName)) {
            return [];
        }

        // Get events from newest to oldest with pagination
        $streamEvents = $this->redis->xRevRange($streamName, '+', '-', $limit + $offset);

        // Apply offset
        $streamEvents = array_slice($streamEvents, $offset, $limit);

        foreach ($streamEvents as $eventId => $payload) {
            $events[] = [
                'event_id' => $eventId,
                'payload' => $payload,
                'timestamp' => $this->getTimestampFromId($eventId),
                'topic' => $topic,
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
            $dlqName = $this->getDLQ($topic);
            $deadLetterStream = $this->getStreamName($dlqName);
            $events = $this->redis->xRange($deadLetterStream, $eventId, $eventId);

            if (empty($events)) {
                return []; // Event not found
            }

            $isFailed = true;
        } else {
            $isFailed = false;
        }

        $payload = reset($events);

        return [
            'event_id' => $eventId,
            'topic' => $topic,
            'payload' => $payload,
            'timestamp' => $this->getTimestampFromId($eventId),
            'is_failed' => $isFailed,
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

    public function getGroupInfo(string $streamName): array
    {
        return $this->redis->xInfo('GROUPS', $streamName);
    }
}
