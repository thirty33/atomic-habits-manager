<?php

namespace App\Observers;

use App\Enums\MessageRole;
use App\Jobs\ProcessConversationJob;
use App\Models\Message;

class MessageObserver
{
    public function created(Message $message): void
    {
        if ($message->role !== MessageRole::User) {
            return;
        }

        ProcessConversationJob::dispatch($message->conversation);
    }
}
