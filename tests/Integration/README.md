# StreamPulse Integration Tests

This folder contains integration tests for the StreamPulse package using a real Redis connection.

## Prerequisites

-   Running Redis server
-   PHP 8.0+
-   Composer dependencies installed

## Test Files

1. `StreamPulseIntegrationTest.php` - Base test case setup
2. `RedisStreamsIntegrationTest.php` - Tests for producer-consumer, retention, DLQ, ordering
3. `EventDispatchingTest.php` - Tests for event dispatching and cross-language compatibility

## Running Tests

Run all integration tests:

```bash
./vendor/bin/pest --group=integration
```

Or run a specific test file:

```bash
./vendor/bin/pest tests/Integration/RedisStreamsIntegrationTest.php
```

## Environment Setup

The tests use Redis database 15 by default to avoid conflicts with other Redis usage.
You can customize the Redis connection in your `.env` file:

```
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Test Coverage

These integration tests verify:

1. **Message Publishing & Consuming** - Validates that messages can be published and consumed correctly
2. **Data Type Preservation** - Checks that different data types are preserved during serialization
3. **Retention Policies** - Tests that stream trimming works correctly
4. **Dead Letter Queues** - Verifies that failed messages are moved to DLQ
5. **Ordered Processing** - Tests that ordered topics process messages in sequence
6. **Consumer Groups** - Verifies multiple consumer groups function independently
7. **Event Dispatching** - Confirms that all expected events are dispatched

## Notes

-   Tests clean up after themselves by flushing the Redis test database
-   Test configuration uses short timeouts to make tests run faster
-   DLQ tests use a custom exception to simulate processing failures
