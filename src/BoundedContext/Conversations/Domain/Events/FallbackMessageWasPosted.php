<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\Events;

use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class FallbackMessageWasPosted extends DomainEvent
{
    public function __construct(
        public readonly int $messageId,
        public readonly int $conversationId,
        public readonly string $body,
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
        return 'conversations.fallback_message_was_posted';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'body' => $this->body,
        ];
    }

    /**
     * @param  array{message_id: int, conversation_id: int, body: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            messageId: (int) $primitives['message_id'],
            conversationId: (int) $primitives['conversation_id'],
            body: (string) $primitives['body'],
        );
    }
}
