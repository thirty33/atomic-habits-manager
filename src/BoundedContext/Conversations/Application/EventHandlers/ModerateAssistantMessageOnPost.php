<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\EventHandlers;

use Core\BoundedContext\Conversations\Application\Actions\ModerateAssistantMessage;
use Core\BoundedContext\Conversations\Application\DTOs\ModerateAssistantMessageData;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasPosted;

/**
 * Reacts to AssistantMessageWasPosted by invoking the moderation Use Case.
 *
 * Declares POLICY = 'heavy' so the relay routes its dispatch to the
 * DispatchDomainEventHeavyJob bucket — the moderation step is another
 * LLM round-trip and shares the same retry / timeout shape as the
 * primary AI response.
 */
final readonly class ModerateAssistantMessageOnPost
{
    public const POLICY = 'heavy';

    public function __construct(
        private ModerateAssistantMessage $useCase,
    ) {}

    public function __invoke(AssistantMessageWasPosted $event): void
    {
        ($this->useCase)(new ModerateAssistantMessageData(
            messageId: $event->messageId,
            conversationId: $event->conversationId,
        ));
    }
}
