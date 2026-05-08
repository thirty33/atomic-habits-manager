<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Responses;

use Core\BoundedContext\Conversations\Domain\Conversation;

final readonly class ConversationResponse
{
    public function __construct(
        public int $conversationId,
        public int $userId,
        public string $title,
        public string $status,
        public string $lastMessageAtIso,
    ) {}

    public static function fromAggregate(Conversation $conversation): self
    {
        $id = $conversation->conversationId();

        if ($id === null) {
            throw new \LogicException('Cannot build ConversationResponse from an unsaved Conversation.');
        }

        return new self(
            conversationId: $id->value(),
            userId: $conversation->userId()->value(),
            title: $conversation->title()->value,
            status: $conversation->status()->value,
            lastMessageAtIso: $conversation->lastMessageAt()->format(DATE_ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'title' => $this->title,
            'status' => $this->status,
            'last_message_at' => $this->lastMessageAtIso,
        ];
    }
}
