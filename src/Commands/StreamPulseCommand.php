<?php

namespace StreamPulse\StreamPulse\Commands;

use Illuminate\Console\Command;

class StreamPulseCommand extends Command
{
    public $signature = 'stream-pulse';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
