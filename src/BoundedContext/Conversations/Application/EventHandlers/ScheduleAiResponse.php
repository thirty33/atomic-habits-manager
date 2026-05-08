<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\EventHandlers;

use Core\BoundedContext\Conversations\Application\Actions\ProcessUserMessageWithAi;
use Core\BoundedContext\Conversations\Application\DTOs\ProcessUserMessageWithAiData;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus;

/**
 * Reacts to UserMessageWasPosted by invoking the AI processing Use Case.
 *
 * Declares POLICY = 'heavy' so the relay routes its dispatch to the
 * DispatchDomainEventHeavyJob bucket (tries=2, timeout=600, queue=heavy).
 * The LLM round-trip can take minutes; the heavy bucket is sized for it.
 */
final readonly class ScheduleAiResponse
{
    public const POLICY = 'heavy';

    public function __construct(
        private ConversationRepository $conversations,
        private ProcessUserMessageWithAi $useCase,
    ) {}

    public function __invoke(UserMessageWasPosted $event): void
    {
        $conversation = $this->conversations->find(ConversationId::from($event->conversationId));

        if ($conversation === null) {
            return;
        }

        if ($conversation->status() !== ConversationStatus::Active) {
            return;
        }

        ($this->useCase)(new ProcessUserMessageWithAiData(
            conversationId: $event->conversationId,
        ));
    }
}
