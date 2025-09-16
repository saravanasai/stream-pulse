<?php

namespace StreamPulse\StreamPulse\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use StreamPulse\StreamPulse\Contracts\StreamUIInterface;
use StreamPulse\StreamPulse\StreamPulse;

/**
 * Command to trim streams across all topics.
 *
 * This command applies the retention policy to all streams,
 * removing older messages based on the configured retention limit.
 * It's designed to be run via scheduler for regular maintenance.
 *
 * Users can run this command manually with:
 *
 * php artisan streampulse:trim-streams
 */
class TrimStreamsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'streampulse:trim-streams';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Apply retention policy to all streams, trimming them to the configured size';

    /**
     * Execute the console command.
     *
     * Trims all streams across all topics based on their retention configuration.
     *
     * @return int Command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $this->info('Starting to trim streams...');
        $exitCode = self::SUCCESS;

        try {
            $driver = StreamPulse::getDriver();

            if (!$driver instanceof StreamUIInterface) {
                $this->error('Driver does not implement StreamUIInterface');
                return self::FAILURE;
            }

            $topics = $driver->listTopics();

            if (empty($topics)) {
                $this->info('No topics found to trim.');
                return $exitCode;
            }

            $success = $this->trimAllTopics($driver, $topics);

            if (!$success) {
                $exitCode = self::FAILURE;
            }
            $this->info("All streams trimmed successfully.");
        } catch (\Exception $e) {
            $this->error("Error trimming streams: " . $e->getMessage());
            Log::error("Error in TrimStreamsCommand", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $exitCode = self::FAILURE;
        }

        return $exitCode;
    }

    /**
     * Trim all topics according to their retention policy.
     *
     * @param mixed $driver The event store driver
     * @param array $topics List of topics to trim
     * @return bool Success status
     */
    protected function trimAllTopics($driver, array $topics): bool
    {
        foreach ($topics as $topic) {
            $this->line("Trimming topic: {$topic}");

            try {
                $driver->applyRetention($topic);
            } catch (\Exception $e) {
                $this->error("Error trimming topic {$topic}: " . $e->getMessage());
                Log::error("Error trimming topic", [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }
}
