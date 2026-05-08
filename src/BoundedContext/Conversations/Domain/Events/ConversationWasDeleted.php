<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\Events;

use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class ConversationWasDeleted extends DomainEvent
{
    public function __construct(
        public readonly int $conversationId,
        public readonly int $userId,
        ?DateTimeImmutable $occurredOn = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredOn ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return 'conversations.was_deleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
        ];
    }

    /**
     * @param  array{conversation_id: int, user_id: int}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            conversationId: (int) $primitives['conversation_id'],
            userId: (int) $primitives['user_id'],
        );
    }
}
