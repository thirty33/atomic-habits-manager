<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Events\MessageSent;
use App\Models\Message;

/**
 * Eloquent observer kept alive only for the Approved → broadcast branch.
 * The Banned branch was replaced in flow 07 by the PostFallbackOnBan
 * Domain Event listener; the Approved branch is replaced in flow 08 by
 * BroadcastApprovedMessage. After flow 08 lands, this file is renamed
 * to .php.delete.
 */
class MessageObserver
{
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
        }
    }
}
