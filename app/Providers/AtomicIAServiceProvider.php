<?php

namespace App\Providers;

use App\Models\Message;
use App\Observers\MessageObserver;
use App\Services\AtomicIAService;
use App\Services\ModerationService;
use Illuminate\Support\ServiceProvider;

class AtomicIAServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AtomicIAService::class, function () {
            return new AtomicIAService(
                provider: config('ai.default'),
                model: config('ai.model'),
            );
        });

        $this->app->bind(ModerationService::class, function () {
            return new ModerationService(
                provider: config('ai.default'),
                model: config('ai.model'),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Message::observe(MessageObserver::class);
    }
}
