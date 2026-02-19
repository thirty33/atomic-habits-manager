<?php

namespace App\Console\Commands;

use App\Jobs\ModerateMessageJob;
use App\Repositories\MessageRepository;
use Illuminate\Console\Command;

class ModeratePendingMessagesCommand extends Command
{
    protected $signature = 'atomic-ia:moderate';

    protected $description = 'Modera los mensajes pendientes de revisión';

    public function handle(MessageRepository $repository): int
    {
        $pending = $repository->getPendingAssistantMessages();

        foreach ($pending as $message) {
            $userMessage = $repository->getLastUserMessageBody($message->conversation);

            $prompt = implode("\n\n", [
                'Mensaje del usuario:',
                $userMessage,
                'Respuesta del asistente:',
                $message->body,
            ]);

            ModerateMessageJob::dispatch($message, $prompt);
        }

        $this->info("Despachados {$pending->count()} jobs de moderación.");

        return self::SUCCESS;
    }
}
