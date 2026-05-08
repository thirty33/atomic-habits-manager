<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\CreateFallbackMessageAction;
use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Events\MessageSent;
use App\Models\Message;

/**
 * Eloquent observer kept alive only for the assistant-message updated
 * branches (Approved → broadcast, Banned → fallback). Both branches are
 * replaced by Domain Event listeners in flows 07 and 08; once those
 * land, this file becomes empty and is renamed to .php.delete.
 *
 * The created branch (which used to dispatch ProcessConversationJob for
 * user messages) is gone — the new path is:
 *   PostUserMessage → MessageRepository.save → UserMessageWasPosted
 *   → Outbox/Relay → DispatchDomainEventHeavyJob → ScheduleAiResponse
 *   → ProcessUserMessageWithAi.
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

            return;
        }

        if ($message->status === MessageStatus::Banned) {
            CreateFallbackMessageAction::execute($message->conversation);
        }
    }
}
