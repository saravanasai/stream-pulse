<?php

namespace StreamPulse\StreamPulse\Contracts;

interface StreamUIInterface
{
    /**
     * List all available topics/streams.
     *
     * @return array
     */
    public function listTopics(): array;

    /**
     * List failed or dead-lettered events.
     *
     * @return array
     */
    public function listFailedEvents(): array;

    /**
     * Get recent events for a specific topic.
     *
     * @param string $topic
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getEventsByTopic(string $topic, int $limit = 50, int $offset = 0): array;

    /**
     * Get detailed payload and metadata of a single event.
     *
     * @param string $topic
     * @param string $eventId
     * @return array
     */
    public function getEventDetails(string $topic, string $eventId): array;
}
