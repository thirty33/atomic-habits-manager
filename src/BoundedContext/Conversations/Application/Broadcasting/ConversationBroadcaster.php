<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Broadcasting;

/**
 * Application port for "tell the frontend something happened on this
 * conversation". The concrete adapter (LaravelEchoConversationBroadcaster)
 * lives in Infrastructure and translates these calls to Laravel
 * broadcast events on the conversation.{id} private channel.
 *
 * Application never imports App\Events or Illuminate\Broadcasting.
 */
interface ConversationBroadcaster
{
    /**
     * Notifies the conversation channel that the conversation status
     * changed (e.g. became 'banned').
     */
    public function statusChanged(int $conversationId, string $status): void;

    /**
     * Notifies the conversation channel that a new approved-or-fallback
     * assistant message is ready to be rendered.
     *
     * @param  array<string, mixed>  $messagePayload  shape mirrors MessageSnapshot::toArray()
     */
    public function messageReady(int $conversationId, array $messagePayload): void;
}
