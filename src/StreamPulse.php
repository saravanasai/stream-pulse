<?php

namespace StreamPulse\StreamPulse;

use StreamPulse\StreamPulse\Contracts\EventStoreDriver;
use InvalidArgumentException;

class StreamPulse
{
    /**
     * The array of created drivers.
     *
     * @var array
     */
    protected array $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected array $customCreators = [];

    /**
     * Get a driver instance.
     *
     * @param string|null $name
     * @return \StreamPulse\StreamPulse\Contracts\EventStoreDriver
     */
    public function driver(?string $name = null): EventStoreDriver
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Get a driver instance.
     *
     * @param string $name
     * @return \StreamPulse\StreamPulse\Contracts\EventStoreDriver
     */
    protected function get(string $name): EventStoreDriver
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given driver.
     *
     * @param string $name
     * @return \StreamPulse\StreamPulse\Contracts\EventStoreDriver
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): EventStoreDriver
    {
        $config = $this->getConfig($name);

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($name);
        }

        $driverMethod = 'create' . ucfirst($name) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$name}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param string $name
     * @return \StreamPulse\StreamPulse\Contracts\EventStoreDriver
     */
    protected function callCustomCreator(string $name): EventStoreDriver
    {
        return $this->customCreators[$name]($this->getConfig($name));
    }

    /**
     * Get the configuration for a driver.
     *
     * @param string $name
     * @return array
     */
    protected function getConfig(string $name): array
    {
        return config("streampulse.drivers.{$name}", []);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return config('streampulse.default', 'redis');
    }

    /**
     * Create an instance of the Redis driver.
     *
     * @return \StreamPulse\StreamPulse\Contracts\EventStoreDriver
     */
    protected function createRedisDriver(): EventStoreDriver
    {
        return app(Drivers\RedisStreamsDriver::class);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string $driver
     * @param \Closure $callback
     * @return $this
     */
    public function extend(string $driver, \Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Publish an event to a topic.
     *
     * @param string $topic
     * @param array $payload
     * @return void
     */
    public function publish(string $topic, array $payload): void
    {
        $this->driver()->publish($topic, $payload);
    }

    /**
     * Consume events from a topic.
     *
     * @param string $topic
     * @param callable $callback
     * @param string $group
     * @return void
     */
    public function consume(string $topic, string $group, callable $callback): void
    {
        $this->driver()->consume($topic, $callback, $group);
    }

    /**
     * Acknowledge a message as processed.
     *
     * @param string $topic
     * @param string $messageId
     * @param string $group
     * @return void
     */
    public function ack(string $topic, string $messageId, string $group): void
    {
        $this->driver()->ack($topic, $messageId, $group);
    }

    /**
     * Mark a message as failed.
     *
     * @param string $topic
     * @param string $messageId
     * @param string $group
     * @return void
     */
    public function fail(string $topic, string $messageId, string $group): void
    {
        $this->driver()->fail($topic, $messageId, $group);
    }
}
