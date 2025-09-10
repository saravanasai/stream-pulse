<?php

namespace StreamPulse\StreamPulse\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use StreamPulse\StreamPulse\Http\Controllers\StreamPulseDashboardController;

class StreamPulseUIServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // No additional registrations needed here
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('stream-pulse.ui.enabled', true)) {
            $this->registerRoutes();
        }
    }

    /**
     * Register the UI routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'prefix' => config('stream-pulse.ui.route_prefix', 'stream-pulse'),
        ], function () {
            Route::get('/', [StreamPulseDashboardController::class, 'index'])->name('stream-pulse.dashboard');
            Route::get('/topics/{topic}', [StreamPulseDashboardController::class, 'topic'])->name('stream-pulse.topic');
            Route::get('/topics/{topic}/events/{eventId}', [StreamPulseDashboardController::class, 'event'])->name('stream-pulse.event');
            Route::get('/failed', [StreamPulseDashboardController::class, 'failed'])->name('stream-pulse.failed');
        });
    }
}
