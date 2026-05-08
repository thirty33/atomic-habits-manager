<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\EventHandlers;

use Core\BoundedContext\Conversations\Application\Actions\PostFallbackMessage;
use Core\BoundedContext\Conversations\Application\DTOs\PostFallbackMessageData;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasBanned;

/**
 * Reacts to AssistantMessageWasBanned by emitting the fallback apology
 * message. The fallback is itself broadcast via BroadcastApprovedMessage
 * (flow 08) since FallbackMessageWasPosted shares the same listener.
 *
 * POLICY = 'default' — quick DB write, tolerant to retries.
 */
final readonly class PostFallbackOnBan
{
    public const POLICY = 'default';

    public function __construct(private PostFallbackMessage $postFallback) {}

    public function __invoke(AssistantMessageWasBanned $event): void
    {
        ($this->postFallback)(new PostFallbackMessageData(
            conversationId: $event->conversationId,
        ));
    }
}
