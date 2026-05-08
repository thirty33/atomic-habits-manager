<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ModerateMessageJob;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Illuminate\Console\Command;

final class ModeratePendingMessagesCommand extends Command
{
    protected $signature = 'atomic-ia:moderate';

    protected $description = 'Despacha un job de moderación por cada mensaje del asistente en estado Pending.';

    public function handle(MessageRepository $messages): int
    {
        $pending = $messages->pendingAssistantMessages()->items();

        $dispatched = 0;
        foreach ($pending as $message) {
            $messageId = $message->messageId();
            if ($messageId === null) {
                continue;
            }

            ModerateMessageJob::dispatch(
                $messageId->value(),
                $message->conversationId()->value(),
            );
            $dispatched++;
        }

        $this->components->info("Despachados {$dispatched} jobs de moderación.");

        return self::SUCCESS;
    }
}
