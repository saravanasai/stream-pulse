<?php

namespace StreamPulse\StreamPulse\Contracts;

interface EventStoreDriver
{
    /**
     * Publish an event to a topic.
     *
     * @param string $topic
     * @param array $payload
     * @return void
     */
    public function publish(string $topic, array $payload): void;

    /**
     * Consume events from a topic.
     *
     * @param string $topic
     * @param callable $callback
     * @param string $group
     * @return void
     */
    public function consume(string $topic, callable $callback, string $group): void;

    /**
     * Acknowledge a message as processed.
     *
     * @param string $topic
     * @param string $messageId
     * @param string $group
     * @return void
     */
    public function ack(string $topic, string $messageId, string $group): void;

    /**
     * Mark a message as failed.
     *
     * @param string $topic
     * @param string $messageId
     * @param string $group
     * @return void
     */
    public function fail(string $topic, string $messageId, string $group): void;
}
