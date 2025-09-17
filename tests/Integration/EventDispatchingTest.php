<?php

namespace StreamPulse\StreamPulse\Tests\Integration;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use StreamPulse\StreamPulse\Tests\TestCase;
use StreamPulse\StreamPulse\Drivers\RedisStreamsDriver;

/**
 * Test Event Dispatching
 */
test('driver dispatches appropriate events during operations', function () {
    Event::fake([
        'stream-pulse.publishing',
        'stream-pulse.published',
        'stream-pulse.consuming',
        'stream-pulse.consumed',
        'stream-pulse.message-processing',
        'stream-pulse.message-processed',
        'stream-pulse.failing',
        'stream-pulse.failed',
        'stream-pulse.retention-applying',
        'stream-pulse.retention-applied'
    ]);

    $driver = new RedisStreamsDriver();
    $topic = 'event-test-topic';
    $group = 'event-test-group';

    // Publish a message
    $driver->publish($topic, ['test' => 'value'], []);

    // Consume the message
    $driver->consume($topic, function () {
        return true;
    }, $group);

    // Apply retention
    $driver->applyRetention($topic);

    // Assert events were dispatched
    Event::assertDispatched('stream-pulse.publishing');
    Event::assertDispatched('stream-pulse.published');
    Event::assertDispatched('stream-pulse.consuming');
    Event::assertDispatched('stream-pulse.consumed');
    Event::assertDispatched('stream-pulse.retention-applying');
    Event::assertDispatched('stream-pulse.retention-applied');
});

/**
 * Test Cross-Language Compatibility
 */
test('messages are serialized with type information for cross-language compatibility', function () {
    $driver = new RedisStreamsDriver();
    $topic = 'cross-language-topic';
    $redis = Redis::connection()->client();

    // Define test values as constants to avoid duplication
    $textValue = 'text value';

    // Publish a message with all data types
    $complexPayload = [
        'string' => $textValue,
        'integer' => 42,
        'float' => 3.14159,
        'boolean' => true,
        'null_value' => null,
        'array' => ['item1', 'item2'],
        'object' => ['key1' => 'value1', 'key2' => 'value2']
    ];

    $driver->publish($topic, $complexPayload, []);

    // Get the raw Redis data
    $streamName = $driver->getStreamName($topic);
    $messages = $redis->xRange($streamName, '-', '+');
    $rawMessage = reset($messages);

    // Verify type prefixes are present as expected
    expect($rawMessage['string'])->toBe($textValue);
    expect($rawMessage['integer'])->toStartWith('__int:');
    expect($rawMessage['float'])->toStartWith('__float:');
    expect($rawMessage['boolean'])->toStartWith('__bool:');
    expect($rawMessage['null_value'])->toBe('__null');
    expect($rawMessage['array'])->toStartWith('__json:');
    expect($rawMessage['object'])->toStartWith('__json:');

    // Now consume it back and verify types are preserved
    $receivedPayload = null;
    $driver->consume($topic, function ($payload) use (&$receivedPayload) {
        $receivedPayload = $payload;
        return true;
    }, 'test-group');

    // Verify data types are preserved
    expect($receivedPayload['string'])->toBe($textValue);
    expect($receivedPayload['integer'])->toBe(42)->toBeInt();
    expect($receivedPayload['float'])->toBe(3.14159)->toBeFloat();
    expect($receivedPayload['boolean'])->toBeTrue();
    expect($receivedPayload['null_value'])->toBeNull();
    expect($receivedPayload['array'])->toBe(['item1', 'item2']);
    expect($receivedPayload['object'])->toBe(['key1' => 'value1', 'key2' => 'value2']);
});
