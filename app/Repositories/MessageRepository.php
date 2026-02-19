<?php

namespace App\Repositories;

use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;

class MessageRepository
{
    public function getPendingAssistantMessages(): Collection
    {
        return Message::query()
            ->where('role', MessageRole::Assistant)
            ->where('status', MessageStatus::Pending)
            ->with('conversation')
            ->get();
    }

    public function getLastUserMessageBody(Conversation $conversation): string
    {
        return $conversation->messages()
            ->where('role', MessageRole::User)
            ->latest('message_id')
            ->value('body') ?? '';
    }
}
