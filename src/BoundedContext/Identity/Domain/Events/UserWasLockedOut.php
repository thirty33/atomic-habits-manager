<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Events;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class UserWasLockedOut extends DomainEvent
{
    public function __construct(
        public readonly EmailAddress $email,
        public readonly string $ipAddress,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(EmailAddress $email, string $ipAddress): self
    {
        return new self(
            email: $email,
            ipAddress: $ipAddress,
            occurredAt: new DateTimeImmutable,
            eventId: bin2hex(random_bytes(16)),
        );
    }

    public static function eventName(): string
    {
        return 'identity.user_was_locked_out';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'email' => $this->email->value(),
            'ip_address' => $this->ipAddress,
        ];
    }

    /**
     * @param  array{email: string, ip_address: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            email: EmailAddress::from($primitives['email']),
            ipAddress: $primitives['ip_address'],
        );
    }
}
