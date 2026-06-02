<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Events;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PersonName;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class UserWasRegistered extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly EmailAddress $email,
        public readonly PersonName $name,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(UserId $userId, EmailAddress $email, PersonName $name): self
    {
        return new self(
            userId: $userId,
            email: $email,
            name: $name,
            occurredAt: new DateTimeImmutable,
            eventId: bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return 'identity.user_was_registered';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'email' => $this->email->value(),
            'name' => $this->name->value(),
        ];
    }

    /**
     * @param  array{user_id: int, email: string, name: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            userId: UserId::from((int) $primitives['user_id']),
            email: EmailAddress::from($primitives['email']),
            name: PersonName::from($primitives['name']),
        );
    }
}
