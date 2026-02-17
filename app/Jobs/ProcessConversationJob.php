<?php

namespace App\Jobs;

use App\Actions\CreateAssistantMessageAction;
use App\Models\Conversation;
use App\Services\AtomicIAService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessConversationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
    ) {}

    public function handle(AtomicIAService $service): void
    {
        $lastMessage = $this->conversation->latestMessage;

        $response = $service->reply($this->conversation, $lastMessage->body);

        CreateAssistantMessageAction::execute($this->conversation, $response);
    }
}
