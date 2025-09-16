<?php

namespace StreamPulse\StreamPulse\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use StreamPulse\StreamPulse\StreamPulse;

class StreamPulseCommand extends Command
{
    public $signature = 'streampulse:consume
                        {topic : The topic to consume events from}
                        {--group= : Consumer group name (defaults to app name)}
                        {--max-messages= : Maximum number of messages to process before exiting}
                        {--sleep=1 : Sleep time in seconds when no messages are available}
                        {--batch-size=10 : Number of messages to process in a batch}
                        {--timeout=1 : Maximum time in seconds to block waiting for messages}';

    public $description = 'Consume events from a StreamPulse topic using registered handlers';

    /**
     * Execute the console command in a continuous processing loop.
     */
    public function handle(): int
    {
        $topic = $this->argument('topic');
        $group = $this->option('group') ?: config('app.name', 'laravel');
        $maxMessages = $this->option('max-messages');
        $sleepTime = (int) $this->option('sleep');
        $batchSize = (int) $this->option('batch-size');

        StreamPulse::validateTopic($topic);

        $this->info("[StreamPulse] Consumer started for topic: {$topic}, group: {$group}");

        // Get the registered handler for the topic
        $handler = StreamPulse::getHandler($topic);
        if (! $handler) {
            $this->error("[StreamPulse] No handler registered for topic: {$topic}");

            return self::FAILURE;
        }

        $running = true;
        $processedMessages = 0;

        // Process messages in a continuous loop
        while ($running) {
            $processedInBatch = $this->processBatch(
                $topic,
                $group,
                $handler,
                $batchSize,
                $maxMessages,
                $processedMessages
            );

            // If we've reached the max messages limit, exit the loop
            if ($maxMessages && $processedMessages >= (int) $maxMessages) {
                $running = false;
            }

            // Sleep if no messages were processed in this batch
            if ($processedInBatch === 0 && $sleepTime > 0) {
                sleep($sleepTime);
            }

            // Check if the connection is still active (useful on Windows) When a user presses Ctrl+C or
            // closes the terminal, this check helps ensure the script shuts down gracefully
            // instead of continuing to run in the background.
            if (connection_status() !== CONNECTION_NORMAL) {
                $running = false;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Process a batch of messages from the topic.
     *
     * @param  string  $topic  The topic to consume from
     * @param  string  $group  The consumer group
     * @param  callable  $handler  The message handler
     * @param  int  $batchSize  Maximum number of messages to process in this batch
     * @param  int|null  $maxMessages  Maximum total messages to process (if set)
     * @param  int  &$processedMessages  Running total of processed messages
     * @return int Number of messages processed in this batch
     */
    protected function processBatch(
        string $topic,
        string $group,
        callable $handler,
        int $batchSize,
        $maxMessages,
        &$processedMessages
    ): int {
        $processedInBatch = 0;

        try {
            // Process up to batchSize messages
            for ($i = 0; $i < $batchSize; $i++) {
                // Check if max messages limit reached
                if ($maxMessages && $processedMessages >= (int) $maxMessages) {
                    break;
                }

                // Track if we processed a message in this iteration
                $beforeCount = $processedMessages;

                // Process a single message
                StreamPulse::consume($topic, $group, function ($payload, $messageId) use (
                    $topic,
                    $group,
                    $handler,
                    &$processedMessages,
                    &$processedInBatch
                ) {
                    try {
                        // Call the registered topic handler
                        $handler($payload, $messageId);

                        // Ack the message after successful processing
                        StreamPulse::getDriver()->ack($topic, $messageId, $group);

                        $this->info("[StreamPulse] Processed message {$messageId} for topic {$topic}");
                        $processedMessages++;
                        $processedInBatch++;
                    } catch (\Throwable $e) {
                        // Log the error
                        Log::error('[StreamPulse] Failed to process message', [
                            'topic' => $topic,
                            'message_id' => $messageId,
                            'payload' => $payload,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        // Move the failed message to DLQ
                        StreamPulse::getDriver()->fail($topic, $messageId, $group);

                        $this->line("[StreamPulse] Message moved to DLQ for topic: {$topic}");
                    }
                });

                // If no message was processed in this iteration, stop trying
                if ($beforeCount === $processedMessages) {
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->error('[StreamPulse] Error in consumer loop: ' . $e->getMessage());
            Log::error('[StreamPulse] Consumer error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Sleep briefly to prevent rapid error loops
            sleep(1);
        }

        return $processedInBatch;
    }
}
