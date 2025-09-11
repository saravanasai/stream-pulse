<?php

namespace StreamPulse\StreamPulse\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use StreamPulse\StreamPulse\Facades\StreamPulse;
use Throwable;

class StreamPulseCommand extends Command
{
    public $signature = 'streampulse:consume
                        {topic : The topic to consume events from}
                        {--group= : Consumer group name (defaults to app name)}
                        {--consumer= : Consumer name within the group (defaults to hostname:pid)}
                        {--max-messages= : Maximum number of messages to process before exiting}
                        {--sleep=1 : Sleep time in seconds when no messages are available}
                        {--batch-size=10 : Number of messages to process in a batch}
                        {--timeout=60 : Maximum time in seconds to block waiting for messages}';

    public $description = 'Consume events from a StreamPulse topic using registered handlers';

    /**
     * Flag to determine if the consumer should continue running.
     */
    protected bool $shouldRun = true;

    /**
     * Count of processed messages.
     */
    protected int $processedCount = 0;

    /**
     * Count of failed messages.
     */
    protected int $failedCount = 0;

    /**
     * Start time of the consumer.
     */
    protected int $startTime;

    /**
     * PCNTL Signal constants for cross-platform compatibility
     */
    protected const SIGNAL_INT = 2;   // SIGINT
    protected const SIGNAL_TERM = 15; // SIGTERM

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $topic = $this->argument('topic');
        $group = $this->option('group') ?: config('app.name', 'laravel');
        $consumer = $this->option('consumer') ?: gethostname() . ':' . getmypid();
        $maxMessages = $this->option('max-messages') ? (int) $this->option('max-messages') : null;
        $sleep = (int) $this->option('sleep');
        $batchSize = (int) $this->option('batch-size');
        $timeout = (int) $this->option('timeout');

        $this->startTime = time();

        // Register signal handlers for graceful shutdown
        $this->registerSignalHandlers();

        // Check if we have a handler for this topic
        if (!StreamPulse::hasHandler($topic)) {
            $this->error("No handler registered for topic: {$topic}");
            $this->info("Register a handler using: StreamPulse::on('{$topic}', function (\$event) { ... })");
            return self::FAILURE;
        }

        $handler = StreamPulse::getHandler($topic);

        $this->info("Starting consumer for topic: {$topic}");
        $this->info("Consumer group: {$group}, Consumer name: {$consumer}");
        $this->info("Press Ctrl+C to stop gracefully");

        // Use a progress bar if max messages is set
        $bar = null;
        if ($maxMessages !== null) {
            $bar = $this->output->createProgressBar($maxMessages);
            $bar->start();
        }

        // Main consumer loop
        while ($this->shouldRun) {
            // Check if we've reached the max messages limit
            if ($maxMessages !== null && $this->processedCount >= $maxMessages) {
                $this->shouldRun = false;
                break;
            }

            // Heartbeat/stats logging
            $this->logHeartbeat();

            try {
                // Consume messages in a batch
                $messagesProcessed = $this->consumeBatch(
                    $topic,
                    $group,
                    $consumer,
                    $handler,
                    $batchSize,
                    $timeout,
                    $bar
                );

                // If no messages were processed, sleep for a bit to avoid tight looping
                if ($messagesProcessed === 0 && $this->shouldRun && $sleep > 0) {
                    sleep($sleep);
                }
            } catch (Throwable $e) {
                $this->error("Error in consumer loop: " . $e->getMessage());
                Log::error("StreamPulse consumer error", [
                    'topic' => $topic,
                    'group' => $group,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Brief pause to avoid hammering if there's a persistent error
                sleep(1);
            }
        }

        if ($bar) {
            $bar->finish();
            $this->newLine(2);
        }

        $this->info("Consumer stopped. Processed {$this->processedCount} messages, {$this->failedCount} failed.");
        $this->info("Total runtime: " . $this->formatRuntime());

        return self::SUCCESS;
    }

    /**
     * Register signal handlers for graceful shutdown.
     */
    protected function registerSignalHandlers(): void
    {
        // Register signal handler for Ctrl+C if supported on Unix-based systems
        if (function_exists('pcntl_signal')) {

            declare(ticks=1);
            pcntl_signal(self::SIGNAL_INT, function () {
                $this->shutdown();
            });
            pcntl_signal(self::SIGNAL_TERM, function () {
                $this->shutdown();
            });
        } else {
            // Windows or systems without pcntl
            $this->info("Signal handling not supported on this platform. Use Ctrl+C to exit.");
        }
    }

    /**
     * Consume a batch of messages from the stream.
     */
    protected function consumeBatch(
        string $topic,
        string $group,
        string $consumer,
        callable $handler,
        int $batchSize,
        int $timeout,
        $progressBar = null
    ): int {
        $processedInBatch = 0;

        // Get the Redis driver directly for batch processing
        $driver = StreamPulse::driver();

        try {
            // Use the driver to get a batch of messages
            $messages = $driver->consumeBatch($topic, $group, $consumer, $batchSize, $timeout);

            if (empty($messages)) {
                return 0;
            }

            foreach ($messages as $messageId => $payload) {
                try {
                    // Call the registered handler with the message payload
                    $handler($payload, $messageId);

                    // Mark as processed
                    $driver->ack($topic, $messageId, $group);
                    $this->processedCount++;
                    $processedInBatch++;

                    if ($progressBar) {
                        $progressBar->advance();
                    } else {
                        $payloadExcerpt = substr(json_encode($payload), 0, 100);
                        if (strlen(json_encode($payload)) > 100) {
                            $payloadExcerpt .= '...';
                        }
                        $this->info("Processed message {$messageId}: {$payloadExcerpt}");
                    }
                } catch (Throwable $e) {
                    $this->failedCount++;

                    if (!$progressBar) {
                        $this->error("Failed to process message {$messageId}: " . $e->getMessage());
                    }

                    Log::error("Failed to process message", [
                        'topic' => $topic,
                        'message_id' => $messageId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // The message will remain in pending state and be retried
                    // Depending on max_retries config, it may end up in the DLQ
                }
            }
        } catch (Throwable $e) {
            $this->error("Error consuming batch: " . $e->getMessage());
            Log::error("StreamPulse batch consumption error", [
                'topic' => $topic,
                'group' => $group,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $processedInBatch;
    }

    /**
     * Log a heartbeat message with stats.
     */
    protected function logHeartbeat(): void
    {
        static $lastHeartbeat = 0;

        $now = time();
        if ($lastHeartbeat === 0) {
            $lastHeartbeat = $now;
            return;
        }

        // Log heartbeat every 60 seconds
        if ($now - $lastHeartbeat >= 60) {
            $runtime = $this->formatRuntime();
            $this->info("Heartbeat: Running for {$runtime}, processed {$this->processedCount} messages, {$this->failedCount} failed");
            $lastHeartbeat = $now;
        }
    }

    /**
     * Format the runtime as a human-readable string.
     */
    protected function formatRuntime(): string
    {
        $runtime = time() - $this->startTime;

        if ($runtime < 60) {
            return "{$runtime}s";
        }

        if ($runtime < 3600) {
            $minutes = floor($runtime / 60);
            $seconds = $runtime % 60;
            return "{$minutes}m {$seconds}s";
        }

        $hours = floor($runtime / 3600);
        $minutes = floor(($runtime % 3600) / 60);
        $seconds = $runtime % 60;
        return "{$hours}h {$minutes}m {$seconds}s";
    }

    /**
     * Gracefully shutdown the consumer.
     */
    public function shutdown(): void
    {
        if ($this->shouldRun) {
            $this->shouldRun = false;
            $this->info("\nGraceful shutdown initiated. Finishing current batch...");
        }
    }
}
