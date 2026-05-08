<?php

namespace App\Providers;

use App\Models\Message;
use App\Observers\MessageObserver;
use Illuminate\Support\ServiceProvider;

class AtomicIAServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ModerationService is now ModerateAssistantMessage + adapter
        // (flow 06). AtomicIAService is now ProcessUserMessageWithAi +
        // adapter (flow 04). No legacy bindings remain here.
    }

    public function boot(): void
    {
        // MessageObserver is kept only for the assistant-message updated
        // branches (Approved → broadcast, Banned → fallback). Both will
        // be replaced by Domain Event listeners in flows 07 and 08.
        Message::observe(MessageObserver::class);
    }
}
