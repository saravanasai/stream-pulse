<?php

namespace StreamPulse\StreamPulse;

use Illuminate\Console\Scheduling\Schedule;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use StreamPulse\StreamPulse\Commands\ProcessPendingMessagesCommand;
use StreamPulse\StreamPulse\Commands\StreamPulseCommand;
use StreamPulse\StreamPulse\Commands\TrimStreamsCommand;
use StreamPulse\StreamPulse\Contracts\StreamUIInterface;
use StreamPulse\StreamPulse\Drivers\RedisStreamsDriver;
use StreamPulse\StreamPulse\Support\TransactionAwareEvents;

class StreamPulseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('stream-pulse')
            ->hasConfigFile('stream-pulse')
            ->hasViews()
            ->hasMigration('create_stream_pulse_table')
            ->hasCommands([
                StreamPulseCommand::class,
                ProcessPendingMessagesCommand::class,
                TrimStreamsCommand::class,
            ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(StreamPulse::class, function () {
            return new StreamPulse;
        });

        // Register the UI Interface - using the same driver instance as the event store
        // This allows for a single connection to be used for both publishing/consuming and UI operations
        $this->app->singleton(StreamUIInterface::class, function ($app) {
            $driver = config('stream-pulse.driver', 'redis');

            if ($driver === 'redis') {
                return $app->make(RedisStreamsDriver::class);
            }

            throw new \InvalidArgumentException("Unsupported UI driver: {$driver}");
        });

        // Register the TransactionAwareEvents class
        $this->app->bind(TransactionAwareEvents::class, function ($app) {
            return new TransactionAwareEvents(
                $app->make(StreamPulse::class)->getDriver()
            );
        });

        // Register the UI Service Provider
        $this->app->register(Providers\StreamPulseUIServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Set up the scheduler for processing pending messages
        $this->configureScheduler();
    }

    /**
     * Configure the scheduler to run the pending messages processor.
     */
    protected function configureScheduler(): void
    {
        // Only set up the scheduler when the schedule command is running
        // and auto-processing is enabled in config
        $autoProcess = config('stream-pulse.auto_process_pending');

        if ($autoProcess && $this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);

                // Process pending messages every 2 minutes
                $schedule->command('streampulse:process-pending')
                    ->everyTwoMinutes()
                    ->withoutOverlapping()
                    ->onOneServer()
                    ->runInBackground();

                // Trim streams every five minutes
                $schedule->command('streampulse:trim-streams')
                    ->everyFiveMinutes()
                    ->withoutOverlapping()
                    ->onOneServer()
                    ->runInBackground();
            });
        }
    }
}
