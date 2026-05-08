<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Application\DTOs\ModerateAssistantMessageData;
use Core\BoundedContext\Conversations\Domain\Exceptions\MessageNotFound;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;

/**
 * Use Case "moderate this assistant reply".
 *
 * Idempotency by state: only acts when the message is still Pending and
 * is an assistant message. Two concurrent invocations may both call the
 * LLM moderator; only the first one persists a status transition (the
 * Domain methods Message::approve / Message::ban exist Pending → terminal).
 */
final readonly class ModerateAssistantMessage
{
    public function __construct(
        private MessageRepository $messages,
        private AiModerationProvider $aiModerator,
    ) {}

    public function __invoke(ModerateAssistantMessageData $data): void
    {
        $message = $this->messages->find(MessageId::from($data->messageId));

        if ($message === null) {
            throw MessageNotFound::withId($data->messageId);
        }

        if ($message->status() !== MessageStatus::Pending) {
            return;
        }

        if ($message->role() !== MessageRole::Assistant) {
            return;
        }

        $userMessage = $this->messages->lastUserMessageBody(
            ConversationId::from($data->conversationId),
        );

        $this->aiModerator->moderate($message, $userMessage);
    }
}
