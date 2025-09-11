<?php

namespace StreamPulse\StreamPulse;

use InvalidArgumentException;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;

class StreamPulse
{
    /**
     * The array of created drivers.
     */
    protected array $drivers = [];

    /**
     * The registered custom driver creators.
     */
    protected array $customCreators = [];

    /**
     * The registered event handlers.
     */
    protected array $handlers = [];

    /**
     * Get a driver instance.
     */
    public function driver(?string $name = null): EventStoreDriver
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Get a driver instance.
     */
    protected function get(string $name): EventStoreDriver
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given driver.
     *
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): EventStoreDriver
    {
        $config = $this->getConfig($name);

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($name);
        }

        $driverMethod = 'create'.ucfirst($name).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$name}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     */
    protected function callCustomCreator(string $name): EventStoreDriver
    {
        return $this->customCreators[$name]($this->getConfig($name));
    }

    /**
     * Get the configuration for a driver.
     */
    protected function getConfig(string $name): array
    {
        return config("streampulse.drivers.{$name}", []);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return config('streampulse.driver', 'redis');
    }

    /**
     * Get topic configuration
     */
    public function getTopicConfig(string $topic): array
    {
        $topicConfig = config("streampulse.topics.{$topic}", []);
        $defaults = config('streampulse.defaults', []);

        return array_merge($defaults, $topicConfig);
    }

    /**
     * Get max retries for a topic
     */
    public function getMaxRetries(string $topic): int
    {
        return $this->getTopicConfig($topic)['max_retries'] ?? 3;
    }

    /**
     * Get dead letter queue name for a topic
     */
    public function getDLQ(string $topic): string
    {
        return $this->getTopicConfig($topic)['dlq'] ?? 'dead_letter';
    }

    /**
     * Get retention setting for a topic
     */
    public function getRetention(string $topic): int
    {
        return $this->getTopicConfig($topic)['retention'] ?? 1000;
    }

    /**
     * Check if a topic is defined in the configuration
     */
    public function isTopicDefined(string $topic): bool
    {
        return array_key_exists($topic, config('streampulse.topics', []));
    }

    /**
     * Check if strict mode is enabled
     */
    public function isStrictModeEnabled(): bool
    {
        return config('streampulse.strict_mode', true);
    }

    /**
     * Validate if a topic can be used
     *
     * @throws \InvalidArgumentException
     */
    public function validateTopic(string $topic): void
    {
        if ($this->isStrictModeEnabled() && ! $this->isTopicDefined($topic)) {
            throw new \InvalidArgumentException(
                "Topic [{$topic}] is not defined in configuration. Enable it in config/stream-pulse.php before publishing."
            );
        }
    }

    /**
     * Create an instance of the Redis driver.
     */
    protected function createRedisDriver(): EventStoreDriver
    {
        return app(Drivers\RedisStreamsDriver::class);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @return $this
     */
    public function extend(string $driver, \Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Publish an event to a topic.
     */
    public function publish(string $topic, array $payload): void
    {
        $this->driver()->publish($topic, $payload);
    }

    /**
     * Publish an event to a topic after the database transaction commits.
     * If no transaction is active, the event will be published immediately.
     */
    public function publishAfterCommit(string $topic, array $payload): void
    {
        $this->getTransactionAwareEvents()->store($topic, $payload);
    }

    /**
     * Get the transaction-aware events instance.
     */
    protected function getTransactionAwareEvents(): Support\TransactionAwareEvents
    {
        return app()->make(Support\TransactionAwareEvents::class, ['driver' => $this->driver()]);
    }

    /**
     * Consume events from a topic.
     */
    public function consume(string $topic, string $group, callable $callback): void
    {
        $this->driver()->consume($topic, $callback, $group);
    }

    /**
     * Acknowledge a message as processed.
     */
    public function ack(string $topic, string $messageId, string $group): void
    {
        $this->driver()->ack($topic, $messageId, $group);
    }

    /**
     * Mark a message as failed.
     */
    public function fail(string $topic, string $messageId, string $group): void
    {
        $this->driver()->fail($topic, $messageId, $group);
    }

    /**
     * Register a handler for a topic.
     *
     * @param  string  $topic  The topic to listen for events on
     * @param  callable  $handler  The handler function that processes events
     * @return $this
     */
    public function on(string $topic, callable $handler): self
    {
        $this->handlers[$topic] = $handler;

        return $this;
    }

    /**
     * Get all registered handlers.
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Get the handler for a specific topic.
     */
    public function getHandler(string $topic): ?callable
    {
        return $this->handlers[$topic] ?? null;
    }

    /**
     * Check if a handler exists for a topic.
     */
    public function hasHandler(string $topic): bool
    {
        return isset($this->handlers[$topic]);
    }
}
