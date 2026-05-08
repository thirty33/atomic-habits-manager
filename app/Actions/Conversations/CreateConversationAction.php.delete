<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationStatus;
use App\Models\Conversation;

final class CreateConversationAction
{
    public static function execute(): Conversation
    {
        return Conversation::create([
            'user_id' => auth()->id(),
            'title' => __('Nueva conversacion'),
            'status' => ConversationStatus::Active,
            'last_message_at' => now(),
        ]);
    }
}
