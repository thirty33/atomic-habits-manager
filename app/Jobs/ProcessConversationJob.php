<?php

declare(strict_types=1);

namespace App\Jobs;

use Core\BoundedContext\Conversations\Application\Actions\ProcessUserMessageWithAi;
use Core\BoundedContext\Conversations\Application\DTOs\ProcessUserMessageWithAiData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Cron-only safety net for the AI-response path.
 *
 * Dispatched by ProcessPendingMessagesCommand (atomic-ia:process) once
 * per minute, NOT by the user-message event path. The event path goes
 * through the outbox + relay + DispatchDomainEventHeavyJob; this Job is
 * only kept alive while we verify the relay drains reliably (see flow
 * 05 deprecation timeline).
 *
 * The Use Case is idempotent by aggregate state: if the latest message
 * is no longer a user message, the call is a no-op.
 */
final class ProcessConversationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 300;

    public function __construct(public int $conversationId)
    {
        $this->onQueue('heavy');
    }

    public function handle(ProcessUserMessageWithAi $useCase): void
    {
        $useCase(new ProcessUserMessageWithAiData(
            conversationId: $this->conversationId,
        ));
    }
}
