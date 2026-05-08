<?php

namespace App\Providers;

use App\Models\Message;
use App\Observers\MessageObserver;
use App\Services\ModerationService;
use Illuminate\Support\ServiceProvider;

class AtomicIAServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ModerationService stays alive until flow 06 promotes its
        // behavior to the ModerateAssistantMessage Use Case.
        $this->app->bind(ModerationService::class, function () {
            return new ModerationService(
                provider: config('ai.default'),
                model: config('ai.model'),
            );
        });
    }

    public function boot(): void
    {
        // MessageObserver is kept for the assistant-message updated
        // branches (Approved → broadcast, Banned → fallback). Both will
        // be replaced by Domain Event listeners in flows 07 and 08.
        Message::observe(MessageObserver::class);
    }
}
