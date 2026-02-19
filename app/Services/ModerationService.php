<?php

namespace App\Services;

use App\Ai\Agents\ModeratorAgent;
use App\Models\Message;

class ModerationService
{
    public function __construct(
        private string $provider,
        private string $model,
    ) {}

    public function moderate(Message $message, string $prompt): void
    {
        (new ModeratorAgent($message))->prompt(
            $prompt,
            provider: $this->provider,
            model: $this->model,
        );
    }
}
