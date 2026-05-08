<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AtomicIAServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ModerationService is now ModerateAssistantMessage + adapter (flow 06).
        // AtomicIAService is now ProcessUserMessageWithAi + adapter (flow 04).
        // MessageObserver is now PostFallbackOnBan + BroadcastApprovedMessage
        // listeners (flows 07/08). This provider has no remaining duties; kept
        // empty for shape symmetry until removed in a future cleanup.
    }

    public function boot(): void
    {
        //
    }
}
