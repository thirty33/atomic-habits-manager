<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\Broadcasting;

use App\Events\ConversationStatusUpdated;
use App\Events\MessageSent;
use App\Models\Conversation as ConversationModel;
use App\Models\Message as MessageModel;
use Core\BoundedContext\Conversations\Application\Broadcasting\ConversationBroadcaster;

/**
 * Adapter for the ConversationBroadcaster port that dispatches the
 * existing Laravel events (MessageSent, ConversationStatusUpdated) so
 * the frontend Echo subscription keeps working with the same channel
 * name and payload shape.
 *
 * Trade-off acknowledged in doc 08: MessageSent currently expects
 * Eloquent models, so this adapter re-loads them from the ids it
 * receives. In a future iteration MessageSent should accept primitives
 * and the double-load disappears; for now the dependency lives in
 * Infrastructure where it belongs.
 */
final readonly class LaravelEchoConversationBroadcaster implements ConversationBroadcaster
{
    public function statusChanged(int $conversationId, string $status): void
    {
        $conv = ConversationModel::query()->find($conversationId);
        if ($conv === null) {
            return;
        }

        ConversationStatusUpdated::dispatch($conv);
    }

    public function messageReady(int $conversationId, array $messagePayload): void
    {
        $messageId = $messagePayload['message_id'] ?? null;
        if ($messageId === null) {
            return;
        }

        $conv = ConversationModel::query()->find($conversationId);
        $msg = MessageModel::query()->find((int) $messageId);

        if ($conv === null || $msg === null) {
            return;
        }

        MessageSent::dispatch($conv, $msg);
    }
}
