<?php

namespace App\Observers;

use App\Actions\CreateFallbackMessageAction;
use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Events\MessageSent;
use App\Jobs\ProcessConversationJob;
use App\Models\Message;

class MessageObserver
{
    public function created(Message $message): void
    {
        if ($message->role !== MessageRole::User) {
            return;
        }

        if ($message->conversation->status !== ConversationStatus::Active) {
            return;
        }

        ProcessConversationJob::dispatch($message->conversation);
    }

    public function updated(Message $message): void
    {
        if ($message->role !== MessageRole::Assistant) {
            return;
        }

        if (! $message->wasChanged('status')) {
            return;
        }

        if ($message->status === MessageStatus::Approved) {
            MessageSent::dispatch($message->conversation, $message);

            return;
        }

        if ($message->status === MessageStatus::Banned) {
            CreateFallbackMessageAction::execute($message->conversation);
        }
    }
}
