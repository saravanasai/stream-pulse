<?php

namespace StreamPulse\StreamPulse;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use StreamPulse\StreamPulse\Commands\StreamPulseCommand;
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
            ->hasCommand(StreamPulseCommand::class);
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
            $driver = config('streampulse.driver', 'redis');

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
}
