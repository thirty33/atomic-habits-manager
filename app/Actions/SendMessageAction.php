<?php

namespace App\Actions;

use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Models\Conversation;
use App\Models\Message;

final class SendMessageAction
{
    public static function execute(Conversation $conversation, array $data = []): Message
    {
        $message = $conversation->messages()->create([
            'role' => MessageRole::User,
            'type' => 'text',
            'body' => data_get($data, 'body'),
            'status' => MessageStatus::Sent,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }
}
