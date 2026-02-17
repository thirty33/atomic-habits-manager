<?php

namespace App\Actions;

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Models\Conversation;
use App\Models\Message;

final class SendMessageAction
{
    public static function execute(array $data = []): Message
    {
        $conversation = self::resolveConversation();

        $message = $conversation->messages()->create([
            'role' => MessageRole::User,
            'type' => 'text',
            'body' => data_get($data, 'body'),
            'status' => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    private static function resolveConversation(): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'status' => ConversationStatus::Active,
            ],
            [
                'title' => __('Nueva conversacion'),
                'last_message_at' => now(),
            ]
        );
    }
}
