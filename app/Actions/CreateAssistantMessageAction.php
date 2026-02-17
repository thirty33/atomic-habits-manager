<?php

namespace App\Actions;

use App\Enums\MessageRole;
use App\Events\MessageSent;
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
            'status' => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);

        MessageSent::dispatch($conversation, $message);

        return $message;
    }
}
