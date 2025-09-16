<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Mockery;


// This trait will provide the necessary mocking setup for our tests
trait RedisMockable
{
    protected function mockRedis()
    {
        // Create a connection mock
        $connection = Mockery::mock('Illuminate\Redis\Connections\Connection');

        // Create a client mock
        $client = Mockery::mock('stdClass');

        // Set up the connection to return our client
        $connection->shouldReceive('client')->andReturn($client);

        // Mock the Redis facade
        Redis::shouldReceive('connection')
            ->andReturn($connection);

        return $client;
    }
}
