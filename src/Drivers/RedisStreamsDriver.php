<?php

namespace StreamPulse\StreamPulse\Drivers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;

class RedisStreamsDriver implements EventStoreDriver
{

    protected $redis;
    protected string $prefix;
    protected string $fullPrefix;

    public function __construct()
    {
        $connectionName = config('stream-pulse.drivers.redis.connection', 'default');
        $this->redis = Redis::connection($connectionName)->client();
        // Get Laravel's Redis prefix
        $redisPrefix = config('database.redis.options.prefix');

        // Get our specific stream prefix
        $streamPrefix = config('stream-pulse.drivers.redis.stream_prefix', 'stream-pulse:');

        // Store both prefixes
        $this->prefix = $streamPrefix;
        $this->fullPrefix = (string) $redisPrefix . $streamPrefix;
    }

    protected function getStreamName(string $topic): string
    {
        return $this->prefix . $topic;
    }

    protected function getRetention(string $topic): int
    {
        return config(
            "stream-pulse.topics.{$topic}.retention",
            config('stream-pulse.defaults.retention', 1000)
        );
    }

    protected function applyRetention(string $topic): void
    {
        $streamName = $this->getStreamName($topic);
        $retention = $this->getRetention($topic);

        if ($retention > 0) {
            $this->redis->xTrim($streamName, 'MAXLEN', '~', $retention);
        }
    }

    protected function getMaxRetries(string $topic): int
    {
        return config(
            "stream-pulse.topics.{$topic}.max_retries",
            config('stream-pulse.defaults.max_retries', 3)
        );
    }

    protected function getDLQ(string $topic): string
    {
        $dlq = config("stream-pulse.topics.{$topic}.dlq");
        if (is_null($dlq)) {
            $dlq = config('stream-pulse.defaults.dlq');
        }
        return $dlq;
    }

    public function publish(string $topic, array $payload): void
    {
        $streamName = $this->getStreamName($topic);

        $formattedPayload = [];
        foreach ($payload as $key => $value) {
            $formattedPayload[$key] = is_array($value) || is_object($value)
                ? json_encode($value)
                : (string) $value;
        }

        $this->redis->xAdd($streamName, '*', $formattedPayload);
        $this->applyRetention($topic);
    }

    protected function checkPendingMessages(string $topic, string $streamName, string $group): void
    {
        $maxRetries = $this->getMaxRetries($topic);
        $pendingMessages = $this->redis->xPending($streamName, $group);

        if (! empty($pendingMessages) && isset($pendingMessages['pending']) && $pendingMessages['pending'] > 0) {
            $pendingDetails = $this->redis->xPendingRange($streamName, $group, '-', '+', 10);

            foreach ($pendingDetails as $pending) {
                $messageId = $pending[0];
                $deliveryCount = $pending[3];

                if ($deliveryCount >= $maxRetries) {
                    $this->fail($topic, $messageId, $group);
                    Log::warning("Message {$messageId} in topic {$topic} exceeded max retries ({$maxRetries}) and was moved to DLQ");
                }
            }
        }
    }

    public function consume(string $topic, callable $callback, string $group): void
    {
        $streamName = $this->getStreamName($topic);
        $consumerName = gethostname() . ':' . getmypid();

        try {
            $this->redis->xGroup('CREATE', $streamName, $group, '0', true);
        } catch (\Exception $e) {
            // Group already exists, continue
        }

        $this->checkPendingMessages($topic, $streamName, $group);

        $newMessages = $this->redis->xReadGroup(
            $group,
            $consumerName,
            [$streamName => '>'],
            10,
            1000
        );

        if ($newMessages) {
            foreach ($newMessages as $messages) {
                foreach ($messages as $messageId => $payload) {
                    try {
                        $callback($payload, $messageId);
                        $this->ack($topic, $messageId, $group);
                    } catch (\Exception $e) {
                        Log::error("Error processing message {$messageId} from {$topic}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public function ack(string $topic, string $messageId, string $group): void
    {
        $streamName = $this->getStreamName($topic);
        $this->redis->xAck($streamName, $group, [$messageId]);
    }

    public function fail(string $topic, string $messageId, string $group): void
    {
        $dlqName = $this->getDLQ($topic);
        $streamName = $this->getStreamName($topic);
        $deadLetterStream = $this->getStreamName($dlqName);

        $pendingMessages = $this->redis->xPendingRange($streamName, $group, $messageId, $messageId, 1);

        if (! empty($pendingMessages)) {
            $message = $this->redis->xRange($streamName, $messageId, $messageId);

            if (! empty($message)) {
                $this->redis->xAdd($deadLetterStream, '*', $message[$messageId]);
                $this->applyRetention($dlqName);
                $this->redis->xAck($streamName, $group, [$messageId]);
            }
        }
    }

    /**
     * List all available topics/streams.
     */
    public function listTopics(): array
    {
        // We need to use the full prefix (Laravel + stream) when searching for keys
        $pattern = $this->fullPrefix . '*';
        $keys = $this->redis->keys($pattern);
        $topics = [];

        foreach ($keys as $key) {
            // Skip failed topics/DLQs
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
        $dlqs[] = config('stream-pulse.defaults.dlq', 'dead_letter');

        // Add topic-specific DLQs
        foreach (config('stream-pulse.topics', []) as $topic => $config) {
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
}
