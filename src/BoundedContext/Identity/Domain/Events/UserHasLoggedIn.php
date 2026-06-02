<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Events;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class UserHasLoggedIn extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(UserId $userId): self
    {
        return new self(
            userId: $userId,
            occurredAt: new DateTimeImmutable,
            eventId: bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return 'identity.user_has_logged_in';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'user_id' => $this->userId->value(),
        ];
    }

    /**
     * @param  array{user_id: int}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(userId: UserId::from((int) $primitives['user_id']));
    }
}
