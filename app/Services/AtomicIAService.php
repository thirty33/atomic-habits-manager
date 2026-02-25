<?php

namespace App\Services;

use App\Ai\Agents\AtomicIAAgent;
use App\Models\Conversation;

class AtomicIAService
{
    public function __construct(
        private string $provider,
        private string $model,
    ) {}

    public function reply(Conversation $conversation, string $message): string
    {
        $agentResponse = (new AtomicIAAgent($conversation))->prompt(
            $message,
            provider: $this->provider,
            model: $this->model,
        );

        $toolMessage = $agentResponse->toolResults
            ->firstWhere('name', 'RespondToUserTool');

        if ($toolMessage && ($msg = $toolMessage->arguments['message'] ?? '') !== '') {
            return $msg;
        }

        $text = (string) $agentResponse;

        return $text !== '' ? $text : '✅ Operación completada correctamente.';
    }
}
