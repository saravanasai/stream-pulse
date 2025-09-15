<?php

namespace StreamPulse\StreamPulse\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use StreamPulse\StreamPulse\Drivers\RedisStreamsDriver;
use StreamPulse\StreamPulse\Contracts\StreamUIInterface;
use StreamPulse\StreamPulse\StreamPulse;

/**
 * Command to process pending messages across all topics.
 *
 * This command automatically checks for pending messages that have exceeded
 * retry limits and moves them to their respective Dead Letter Queues.
 * It's designed to be run via scheduler with no additional configuration needed.
 *
 * The command is automatically scheduled to run every 2 minutes via the package's
 * service provider, as long as auto_process_pending is enabled in the config.
 *
 * This eliminates the need for users to manually schedule this command in their
 * application's Console\Kernel.php file. However, users can still manually run
 * this command with:
 *
 * php artisan streampulse:process-pending
 */
class ProcessPendingMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'streampulse:process-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Process pending messages and move them to DLQ if they exceed retry limits';

    /**
     * Execute the console command.
     *
     * Processes all pending messages across all topics and their consumer groups,
     * moving messages that exceed retry limits to their respective Dead Letter Queues.
     *
     * @return int Command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $this->info('Starting to process pending messages...');
        $exitCode = self::SUCCESS;

        try {
            // We need a driver that implements both interfaces
            $driver = StreamPulse::getDriver();

            if (!$driver instanceof StreamUIInterface) {
                $this->error('Driver does not implement StreamUIInterface');
                return self::FAILURE;
            }

            $topics = $driver->listTopics();

            if (empty($topics)) {
                return $exitCode;
            }

            $success = $this->processAllTopics($driver, $topics);

            if (!$success) {
                $exitCode = self::FAILURE;
            }
            $this->info("All pending messages processed successfully.");
        } catch (\Exception $e) {
            $this->error("Error processing pending messages: " . $e->getMessage());
            Log::error("Error in ProcessPendingMessagesCommand", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $exitCode = self::FAILURE;
        }

        return $exitCode;
    }

    /**
     * Process pending messages for all topics.
     *
     * @param RedisStreamsDriver $driver The event store driver
     * @param array $topics List of topics to process
     * @return int Number of messages processed or -1 on failure
     */
    protected function processAllTopics(RedisStreamsDriver $driver, array $topics): bool
    {
        foreach ($topics as $topic) {
            $this->line("Processing topic: {$topic}");

            // Get all consumer groups for this topic
            try {
                $streamName = $driver->getStreamName($topic);
                $groupInfo = $driver->getGroupInfo($streamName);

                if (empty($groupInfo)) {
                    continue;
                }

                foreach ($groupInfo as $group) {
                    $groupName = $group['name'];
                    $pendingCount = $group['pending'];

                    if ($pendingCount > 0) {
                        $this->line("  Processing group: {$groupName} ({$pendingCount} pending messages)");
                        $driver->checkPendingMessages($topic, $streamName, $groupName);
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error processing topic {$topic}: " . $e->getMessage());
                Log::error("Error processing topic", [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }
}
