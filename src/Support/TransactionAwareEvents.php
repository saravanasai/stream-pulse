<?php

namespace StreamPulse\StreamPulse\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\DB;
use StreamPulse\StreamPulse\Contracts\EventStoreDriver;

class TransactionAwareEvents
{
    /**
     * The events that should be dispatched after the database transaction commits.
     *
     * @var array
     */
    protected $pendingEvents = [];

    /**
     * The event store driver instance.
     *
     * @var \StreamPulse\StreamPulse\Contracts\EventStoreDriver
     */
    protected $driver;

    /**
     * Create a new transaction-aware events instance.
     *
     * @param  \StreamPulse\StreamPulse\Contracts\EventStoreDriver  $driver
     * @return void
     */
    public function __construct(EventStoreDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Store an event to be published after the database transaction commits.
     *
     * @param  string  $topic
     * @param  array  $payload
     * @return void
     */
    public function store(string $topic, array $payload): void
    {
        $connection = $this->resolveConnection();

        if ($this->isTransactionActive($connection)) {
            $this->storeEvent($connection, $topic, $payload);
        } else {
            // If no transaction is active, publish immediately
            $this->driver->publish($topic, $payload);
        }
    }

    /**
     * Store an event for the given connection to be published after commit.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $topic
     * @param  array  $payload
     * @return void
     */
    protected function storeEvent(ConnectionInterface $connection, string $topic, array $payload): void
    {
        $connectionName = $this->getConnectionName($connection);

        // Initialize the array for this connection if it doesn't exist
        if (! isset($this->pendingEvents[$connectionName])) {
            $this->pendingEvents[$connectionName] = [];

            // Register the commit and rollback callbacks
            DB::afterCommit(function () use ($connectionName) {
                $this->publishPendingEvents($connectionName);
            });

            DB::afterRollback(function () use ($connectionName) {
                $this->discardPendingEvents($connectionName);
            });
        }

        // Add the event to the pending list
        $this->pendingEvents[$connectionName][] = [
            'topic' => $topic,
            'payload' => $payload,
        ];
    }

    /**
     * Publish all pending events for a specific connection.
     *
     * @param  string  $connectionName
     * @return void
     */
    protected function publishPendingEvents(string $connectionName): void
    {
        if (! isset($this->pendingEvents[$connectionName])) {
            return;
        }

        foreach ($this->pendingEvents[$connectionName] as $event) {
            $this->driver->publish($event['topic'], $event['payload']);
        }

        $this->discardPendingEvents($connectionName);
    }

    /**
     * Discard all pending events for a specific connection.
     *
     * @param  string  $connectionName
     * @return void
     */
    protected function discardPendingEvents(string $connectionName): void
    {
        unset($this->pendingEvents[$connectionName]);
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function resolveConnection(): ConnectionInterface
    {
        $resolver = app(ConnectionResolverInterface::class);

        return $resolver->connection(null);
    }

    /**
     * Determine if a transaction is active on the given connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return bool
     */
    protected function isTransactionActive(ConnectionInterface $connection): bool
    {
        return $connection->transactionLevel() > 0;
    }

    /**
     * Get the name of the database connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return string
     */
    protected function getConnectionName(ConnectionInterface $connection): string
    {
        // Laravel connections typically have a name property, but the interface doesn't define it
        // This is a safe fallback approach
        return spl_object_hash($connection);
    }
}
