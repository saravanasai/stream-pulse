<?php

namespace StreamPulse\StreamPulse\Contracts;

interface StreamUIInterface
{
    /**
     * List all available topics/streams.
     */
    public function listTopics(): array;

    /**
     * List failed or dead-lettered events.
     */
    public function listFailedEvents(): array;

    /**
     * Get recent events for a specific topic.
     */
    public function getEventsByTopic(string $topic, int $limit = 50, int $offset = 0): array;

    /**
     * Get detailed payload and metadata of a single event.
     */
    public function getEventDetails(string $topic, string $eventId): array;
}
