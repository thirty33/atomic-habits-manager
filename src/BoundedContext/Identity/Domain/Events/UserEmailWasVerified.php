<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Events;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class UserEmailWasVerified extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly DateTimeImmutable $verifiedAt,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? $verifiedAt,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function at(UserId $userId, DateTimeImmutable $verifiedAt): self
    {
        return new self(
            userId: $userId,
            verifiedAt: $verifiedAt,
            occurredAt: $verifiedAt,
            eventId: bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return 'identity.user_email_was_verified';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'verified_at' => $this->verifiedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param  array{user_id: int, verified_at: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            userId: UserId::from((int) $primitives['user_id']),
            verifiedAt: new DateTimeImmutable($primitives['verified_at']),
        );
    }
}
