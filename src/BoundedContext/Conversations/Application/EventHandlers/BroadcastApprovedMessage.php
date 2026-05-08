<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\EventHandlers;

use Core\BoundedContext\Conversations\Application\Broadcasting\ConversationBroadcaster;
use Core\BoundedContext\Conversations\Application\ReadModels\MessageSnapshot;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasApproved;
use Core\BoundedContext\Conversations\Domain\Events\FallbackMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageId;

/**
 * Reacts to AssistantMessageWasApproved or FallbackMessageWasPosted by
 * pushing the resulting message to the conversation channel so the
 * frontend renders it without a page refresh.
 *
 * POLICY = 'default' — broadcasts are cheap and tolerant to retries.
 *
 * Listens to two events (a union type) because both result in "message
 * ready for the user" — same payload shape, same channel, same handler.
 */
final readonly class BroadcastApprovedMessage
{
    public const POLICY = 'default';

    public function __construct(
        private MessageRepository $messages,
        private ConversationBroadcaster $broadcaster,
    ) {}

    public function __invoke(AssistantMessageWasApproved|FallbackMessageWasPosted $event): void
    {
        $message = $this->messages->find(MessageId::from($event->messageId));

        if ($message === null) {
            return;
        }

        $snapshot = new MessageSnapshot(
            messageId: $message->messageId()->value(),
            conversationId: $message->conversationId()->value(),
            role: $message->role()->value,
            type: $message->type()->value,
            body: $message->body()?->value,
            mediaUrl: $message->mediaUrl(),
            status: $message->status()->value,
            metadata: $message->metadata(),
            createdAtHm: $message->createdAt()?->format('H:i') ?? '',
        );

        $this->broadcaster->messageReady(
            conversationId: $event->conversationId,
            messagePayload: $snapshot->toArray(),
        );
    }
}
