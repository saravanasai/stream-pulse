<?php

namespace StreamPulse\StreamPulse\Contracts;

interface EventStoreDriver
{
    /**
     * Publish an event to a topic.
     */
    public function publish(string $topic, array $payload): void;

    /**
     * Consume events from a topic.
     */
    public function consume(string $topic, callable $callback, string $group): void;

    /**
     * Acknowledge a message as processed.
     */
    public function ack(string $topic, string $messageId, string $group): void;

    /**
     * Mark a message as failed.
     */
    public function fail(string $topic, string $messageId, string $group): void;
}
