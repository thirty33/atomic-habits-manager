<?php

namespace App\Actions;

use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Models\Conversation;
use App\Models\Message;

final class CreateAssistantMessageAction
{
    public static function execute(Conversation $conversation, string $body): Message
    {
        $message = $conversation->messages()->create([
            'role' => MessageRole::Assistant,
            'type' => 'text',
            'body' => $body,
            'status' => MessageStatus::Pending,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }
}
