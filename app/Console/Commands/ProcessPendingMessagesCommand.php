<?php

namespace App\Console\Commands;

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Jobs\ProcessConversationJob;
use App\Models\Conversation;
use Illuminate\Console\Command;

class ProcessPendingMessagesCommand extends Command
{
    protected $signature = 'atomic-ia:process';

    protected $description = 'Procesa conversaciones pendientes de respuesta de la IA';

    public function handle(): int
    {
        $conversations = Conversation::where('status', ConversationStatus::Active)
            ->whereHas('latestMessage', fn ($q) => $q->where('role', MessageRole::User))
            ->get();

        foreach ($conversations as $conversation) {
            ProcessConversationJob::dispatch($conversation);
        }

        $this->info("Despachados {$conversations->count()} jobs de conversación.");

        return self::SUCCESS;
    }
}
