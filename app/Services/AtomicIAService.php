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
        return (new AtomicIAAgent($conversation))->prompt(
            $message,
            provider: $this->provider,
            model: $this->model,
        );
    }
}
