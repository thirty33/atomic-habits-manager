<?php

namespace App\Actions\Conversations;

use App\Actions\Contracts\UpdateAction;
use App\Enums\ConversationStatus;
use App\Events\ConversationStatusUpdated;
use App\Models\Conversation;

final class BanConversationAction implements UpdateAction
{
    public static function execute(int $id, array $data = []): void
    {
        $conversation = Conversation::findOrFail($id);

        $conversation->update([
            'status' => ConversationStatus::Banned,
        ]);

        ConversationStatusUpdated::dispatch($conversation);
    }
}
