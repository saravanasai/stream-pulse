<?php

namespace StreamPulse\StreamPulse;

use InvalidArgumentException;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;
use StreamPulse\StreamPulse\Drivers\RedisStreamsDriver;


/**
 * StreamPulse - Main facade for interacting with the event streaming system.
 *
 * This class provides a centralized interface for publishing and consuming
 * events across different streaming drivers. It manages driver instantiation,
 * configuration loading, and event handling registration.
 */
class StreamPulse
{

    /**
     * Registry of topic handlers.
     *
     * @var array<string, callable> Map of topic names to their handler functions
     */
    protected static array $handlers = [];

    /**
     * Singleton instance of the event store driver.
     *
     * @var EventStoreDriver|null Cached driver instance to prevent multiple connections
     */
    protected static ?EventStoreDriver $driverInstance = null;

    /**
     * Supported driver type constants.
     */
    private const DRIVER_REDIS = 'redis';

    /**
     * Get the configured driver name from application config.
     *
     * @return string The name of the configured driver
     */
    protected static function getDriverName(): string
    {
        return config('stream-pulse.driver', self::DRIVER_REDIS);
    }
    /**
     * Resolve and cache the driver instance as a singleton.
     *
     * This method ensures only one driver connection is created per application lifecycle,
     * improving resource utilization by reusing the same connection.
     *
     * @return EventStoreDriver The resolved driver instance
     * @throws InvalidArgumentException When an unsupported driver is configured
     */
    public static function getDriver(): EventStoreDriver
    {
        if (self::$driverInstance !== null) {
            return self::$driverInstance;
        }

        $driver = self::getDriverName();

        self::$driverInstance = match ($driver) {
            self::DRIVER_REDIS => new RedisStreamsDriver(),
            default => throw new InvalidArgumentException("Driver [$driver] is not supported."),
        };

        return self::$driverInstance;
    }

    /**
     * Get topic configuration with defaults applied.
     *
     * Merges the default configuration with topic-specific configuration
     * to ensure all required settings are available.
     *
     * @param string $topic The topic name
     * @return array<string, mixed> Complete configuration for the topic
     */
    protected static function getTopicConfig(string $topic): array
    {
        $defaults = config('stream-pulse.defaults', []);
        $topicConfig = config("stream-pulse.topics.$topic", []);

        return array_merge($defaults, $topicConfig);
    }

    /**
     * Publish an event to a topic.
     *
     * @param string $topic The topic to publish to
     * @param array<string, mixed> $payload The event data to publish
     * @return void
     * @throws InvalidArgumentException When topic validation fails in strict mode
     */
    public static function publish(string $topic, array $payload): void
    {
        self::validateTopic($topic);

        $config = self::getTopicConfig($topic);
        $driver = self::getDriver();

        $driver->publish($topic, $payload, $config);
    }

    /**
     * Schedule an event to be published after the current database transaction commits.
     *
     * This method defers event publication until after the database transaction completes
     * successfully, ensuring data consistency between the database and event stream.
     *
     * @param string $topic The topic to publish to
     * @param array<string, mixed> $payload The event data to publish
     * @return void
     * @throws InvalidArgumentException When topic validation fails in strict mode
     */
    public static function publishAfterCommit(string $topic, array $payload): void
    {
        self::validateTopic($topic);
        $config = self::getTopicConfig($topic);
        app(Support\TransactionAwareEvents::class)->store($topic, $payload, $config);
    }

    /**
     * Register a handler function for a specific topic.
     *
     * @param string $topic The topic to register a handler for
     * @param callable $handler The function to handle events from this topic
     * @return void
     */
    public static function on(string $topic, callable $handler): void
    {
        self::$handlers[$topic] = $handler;
    }

    /**
     * Retrieve the registered handler for a topic.
     *
     * @param string $topic The topic to get the handler for
     * @return callable|null The handler function if registered, null otherwise
     */
    public static function getHandler(string $topic): ?callable
    {
        return self::$handlers[$topic] ?? null;
    }

    /**
     * Consume events from a topic using the registered or provided handler.
     *
     * @param string $topic The topic to consume from
     * @param string $group The consumer group name
     * @param callable|null $callback Optional callback to use instead of registered handler
     * @return void
     * @throws InvalidArgumentException When topic validation fails in strict mode
     */
    public static function consume(string $topic, string $group, ?callable $callback = null): void
    {
        self::validateTopic($topic);

        $driver = self::getDriver();
        $handler = $callback ?? (self::$handlers[$topic] ?? null);

        if ($handler === null) {
            throw new InvalidArgumentException("No handler registered for topic [$topic]");
        }

        $driver->consume($topic, $handler, $group);
    }

    /**
     * Validate that a topic exists in configuration when strict mode is enabled.
     *
     * @param string $topic The topic to validate
     * @return void
     * @throws InvalidArgumentException When topic is not defined in strict mode
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
