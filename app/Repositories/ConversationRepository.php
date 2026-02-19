<?php

namespace App\Repositories;

use App\Models\Conversation;
use Illuminate\Support\Collection;

class ConversationRepository
{
    public function getLatestActiveByUserWithMessages(int $userId): ?Conversation
    {
        return Conversation::query()
            ->where('user_id', $userId)
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->latest('last_message_at')
            ->first();
    }

    public function getAllByUserWithLatestMessage(int $userId): Collection
    {
        return Conversation::query()
            ->where('user_id', $userId)
            ->with('latestMessage')
            ->latest('last_message_at')
            ->get();
    }

    public function getByIdAndUser(int $conversationId, int $userId): ?Conversation
    {
        return Conversation::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->first();
    }
}
