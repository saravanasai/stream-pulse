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
            'prefix' => config('stream-pulse.ui.route_prefix', 'streampulse'),
            'middleware' => ['web', 'auth'],
        ], function () {
            Route::get('/', [StreamPulseDashboardController::class, 'index'])->name('streampulse.dashboard');
            Route::get('/topics/{topic}', [StreamPulseDashboardController::class, 'topic'])->name('streampulse.topic');
            Route::get('/topics/{topic}/events/{eventId}', [StreamPulseDashboardController::class, 'event'])->name('streampulse.event');
            Route::get('/failed', [StreamPulseDashboardController::class, 'failed'])->name('streampulse.failed');
        });
    }
}
