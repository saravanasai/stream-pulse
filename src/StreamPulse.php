<?php

namespace StreamPulse\StreamPulse;

use InvalidArgumentException;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;
use StreamPulse\StreamPulse\Drivers\RedisStreamsDriver;

class StreamPulse
{
    protected static array $handlers = [];

    protected static ?EventStoreDriver $driverInstance = null;

    private const DRIVER_REDIS = 'redis';

    /**
     * Get the driver name from config.
     */
    protected static function getDriverName(): string
    {
        return config('stream-pulse.driver', 'redis');
    }

    /**
     * Resolve and cache the driver instance.
     */
    public static function getDriver(): EventStoreDriver
    {
        if (self::$driverInstance) {
            return self::$driverInstance;
        }

        $driver = self::getDriverName();

        switch ($driver) {
            case self::DRIVER_REDIS:
                self::$driverInstance = new RedisStreamsDriver;
                break;
            default:
                throw new InvalidArgumentException("Driver [$driver] is not supported.");
        }

        return self::$driverInstance;
    }

    /**
     * Get topic config, merged with defaults and optional overrides.
     */
    protected static function getTopicConfig(string $topic): array
    {
        $defaults = config('stream-pulse.defaults', []);
        $topicConfig = config("stream-pulse.topics.$topic", []);

        return array_merge($defaults, $topicConfig);
    }

    /**
     * Publish an event to a topic.
     */
    public static function publish(string $topic, array $payload): void
    {
        self::validateTopic($topic);

        $config = self::getTopicConfig($topic);
        $driver = self::getDriver();

        $driver->publish($topic, $payload, $config);
    }

    /**
     * Publish after DB transaction commits.
     */
    public static function publishAfterCommit(string $topic, array $payload): void
    {
        self::validateTopic($topic);
        app(Support\TransactionAwareEvents::class)->store($topic, $payload);
    }

    /**
     * Register a handler for a topic.
     */
    public static function on(string $topic, callable $handler): void
    {
        self::$handlers[$topic] = $handler;
    }

    public static function getHandler(string $topic): ?callable
    {
        return self::$handlers[$topic] ?? null;
    }

    /**
     * Consume events from a topic.
     */
    public static function consume(string $topic, string $group, ?callable $callback = null): void
    {
        self::validateTopic($topic);

        $driver = self::getDriver();
        $handler = $callback ?? (self::$handlers[$topic] ?? null);

        $driver->consume($topic, $handler, $group);
    }

    /**
     * Validate topic existence if strict mode is enabled.
     */
    public static function validateTopic(string $topic): void
    {
        $strict = config('stream-pulse.strict_mode', false);
        $topics = array_keys(config('stream-pulse.topics', []));

        if ($strict && ! in_array($topic, $topics)) {
            throw new InvalidArgumentException(
                "Topic [$topic] is not defined in configuration. Enable it in config/stream-pulse.php before publishing."
            );
        }
    }
}
