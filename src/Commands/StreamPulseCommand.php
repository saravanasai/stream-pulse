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
                        {--consumer= : Consumer name within the group (defaults to hostname:pid)}
                        {--max-messages= : Maximum number of messages to process before exiting}
                        {--sleep=1 : Sleep time in seconds when no messages are available}
                        {--batch-size=1 : Number of messages to process in a batch}
                        {--timeout=10 : Maximum time in seconds to block waiting for messages}';

    public $description = 'Consume events from a StreamPulse topic using registered handlers';

    /**
     * Execute the console command (simplified for MVP).
     */
    public function handle(): int
    {
        $topic = $this->argument('topic');
        $group = $this->option('group') ?: config('app.name', 'laravel');
        $consumer = $this->option('consumer') ?: gethostname().':'.getmypid();

        StreamPulse::validateTopic($topic);

        $this->info("[StreamPulse] Consumer started for topic: {$topic}, group: {$group} , consumer: {$consumer}");

        // Get the registered handler for the topic
        $handler = StreamPulse::getHandler($topic);

        // Consume messages using the StreamPulse API and the registered handler
        StreamPulse::consume($topic, $group, function ($payload, $messageId) use ($topic, $group, $handler) {
            try {
                // Call the registered topic handler
                $handler($payload, $messageId);
                // Ack the message after successful processing
                StreamPulse::getDriver()->ack($topic, $messageId, $group);
                Log::info('[StreamPulse] Processed message', [
                    'topic' => $topic,
                    'message_id' => $messageId,
                    'payload' => $payload,
                ]);
                $this->info("[StreamPulse] Processed message {$messageId} for topic {$topic}");
            } catch (\Throwable $e) {
                Log::error('[StreamPulse] Failed to process message', [
                    'topic' => $topic,
                    'message_id' => $messageId,
                    'payload' => $payload,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("[StreamPulse] Failed to process message {$messageId} for topic {$topic}: ".$e->getMessage());
                // Do not ack, message will remain pending and be retried or sent to DLQ
            }
        });

        $this->info("[StreamPulse] Consumer stopped for topic: {$topic}, group: {$group}");

        return self::SUCCESS;
    }
}
