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
     * Consume a batch of messages from a topic.
     * Returns an array of message ID => payload.
     */
    public function consumeBatch(string $topic, string $group, string $consumer, int $count, int $timeout = 0): array;

    /**
     * Acknowledge a message as processed.
     */
    public function ack(string $topic, string $messageId, string $group): void;

    /**
     * Mark a message as failed.
     */
    public function fail(string $topic, string $messageId, string $group): void;
}
