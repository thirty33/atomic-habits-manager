<?php

namespace App\Jobs;

use App\Actions\CreateAssistantMessageAction;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Services\AtomicIAService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessConversationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(
        public Conversation $conversation,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->conversation->conversation_id;
    }

    public function handle(AtomicIAService $service): void
    {
        if ($this->conversation->status !== ConversationStatus::Active) {
            return;
        }

        $lastMessage = $this->conversation->latestMessage;

        \Log::info('[ProcessConversationJob] Debug prompt', [
            'conversation_id' => $this->conversation->conversation_id,
            'message_id' => $lastMessage->message_id,
            'role' => $lastMessage->role->value,
            'body' => $lastMessage->body,
        ]);

        $response = $service->reply($this->conversation, $lastMessage->body);

        CreateAssistantMessageAction::execute($this->conversation, $response);
    }
}
