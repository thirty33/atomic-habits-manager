<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\EventHandlers;

use Core\BoundedContext\Conversations\Application\Broadcasting\ConversationBroadcaster;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasBanned;

/**
 * Reacts to ConversationWasBanned by pushing a status update to the
 * conversation channel so the frontend disables the input.
 *
 * POLICY = 'default' — broadcasts are fast and tolerant to retries; no
 * need for the 'heavy' bucket.
 */
final readonly class BroadcastConversationStatus
{
    public const POLICY = 'default';

    public function __construct(private ConversationBroadcaster $broadcaster) {}

    public function __invoke(ConversationWasBanned $event): void
    {
        $this->broadcaster->statusChanged(
            conversationId: $event->conversationId,
            status: 'banned',
        );
    }
}
