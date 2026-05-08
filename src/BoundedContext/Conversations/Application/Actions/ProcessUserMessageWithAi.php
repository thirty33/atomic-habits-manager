<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\DTOs\ProcessUserMessageWithAiData;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use DateTimeImmutable;

/**
 * Use Case "process the latest user message with the AI and persist its
 * Pending assistant reply".
 *
 * Idempotency by state — never by Cache::lock or ShouldBeUnique:
 *  - Only acts when the conversation is Active.
 *  - Only acts when the latest message is from the user.
 * Two concurrent invocations may both call the LLM, but only one will
 * persist a new assistant message; the other will see the updated state
 * on its next entry and exit.
 */
final readonly class ProcessUserMessageWithAi
{
    public function __construct(
        private ConversationRepository $conversations,
        private MessageRepository $messages,
        private AiResponseProvider $aiProvider,
    ) {}

    public function __invoke(ProcessUserMessageWithAiData $data): void
    {
        $conversationId = ConversationId::from($data->conversationId);
        $conversation = $this->conversations->find($conversationId);

        if ($conversation === null) {
            return;
        }

        if ($conversation->status() !== ConversationStatus::Active) {
            return;
        }

        $latest = $this->messages->latestForConversation($conversationId);

        if ($latest === null || $latest->role() !== MessageRole::User || $latest->body() === null) {
            return;
        }

        $body = $this->aiProvider->respondTo($conversation, $latest->body());

        $assistant = Message::postAssistant($conversationId, $body);
        $this->messages->save($assistant);

        $conversation->touchLastMessageAt(new DateTimeImmutable);
        $this->conversations->save($conversation);
    }
}
