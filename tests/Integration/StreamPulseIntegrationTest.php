<?php

namespace StreamPulse\StreamPulse\Tests\Integration;

use Illuminate\Support\Facades\Redis;
use StreamPulse\StreamPulse\Tests\TestCase;
use StreamPulse\StreamPulse\Drivers\RedisStreamsDriver;

/**
 * Integration tests for StreamPulse with real Redis connection
 *
 * Note: This test suite requires a working Redis connection.
 * Set REDIS_HOST environment variable before running tests.
 */
class StreamPulseIntegrationTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Use a separate Redis database for testing
        config([
            'database.redis.default.database' => 15,  // Use database 15 for testing
            'stream-pulse.drivers.redis.connection' => 'default',
            'stream-pulse.drivers.redis.stream_prefix' => 'test-streampulse:',
            'stream-pulse.defaults.max_retries' => 3,
            'stream-pulse.defaults.min_idle_time' => 1000, // 1 second for faster testing
            'stream-pulse.defaults.retention' => 100,
            'stream-pulse.defaults.dlq' => 'test-dlq'
        ]);

        // Clean up Redis test database before each test
        Redis::connection('default')->flushdb();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Clean up Redis test database after each test
        Redis::connection('default')->flushdb();

        parent::tearDown();
    }
}
