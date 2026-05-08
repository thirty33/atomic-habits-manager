<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessConversationJob;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Illuminate\Console\Command;

/**
 * Cron-only safety net for "user message awaits an AI reply".
 *
 * Deprecated in fase 2 (flow 05): removed from `routes/console.php`
 * because the outbox + relay + DispatchDomainEventHeavyJob path already
 * drains pending user messages. This file stays on disk so it can be
 * reactivated quickly if production reveals an outbox-drain regression.
 *
 * The Use Case `ProcessUserMessageWithAi` is idempotent by aggregate
 * state: invoking it on a conversation whose latest message is no
 * longer a user message is a no-op.
 */
final class ProcessPendingMessagesCommand extends Command
{
    protected $signature = 'atomic-ia:process';

    protected $description = 'Cron-only safety net: dispatch ProcessConversationJob for conversations awaiting an AI reply.';

    public function handle(ConversationRepository $conversations): int
    {
        $ids = $conversations->idsAwaitingAiResponse();

        foreach ($ids as $conversationId) {
            ProcessConversationJob::dispatch($conversationId);
        }

        $this->components->info('Despachados '.count($ids).' jobs de conversación.');

        return self::SUCCESS;
    }
}
