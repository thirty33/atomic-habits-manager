<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\ModerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ModerateMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public string $prompt,
    ) {}

    public function handle(ModerationService $service): void
    {
        $service->moderate($this->message, $this->prompt);
    }
}
