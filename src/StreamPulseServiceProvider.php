<?php

namespace StreamPulse\StreamPulse;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use StreamPulse\StreamPulse\Commands\StreamPulseCommand;

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
            ->hasConfigFile()
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
    }
}
