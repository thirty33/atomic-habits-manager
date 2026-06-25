<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role\Events;

use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleName;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class RoleWasCreated extends DomainEvent
{
    public function __construct(
        public readonly RoleId $roleId,
        public readonly RoleName $name,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(RoleId $roleId, RoleName $name): self
    {
        return new self(roleId: $roleId, name: $name);
    }

    public static function eventName(): string
    {
        return 'access.role_was_created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'role_id' => $this->roleId->value(),
            'name' => $this->name->value(),
        ];
    }

    /**
     * @param  array{role_id: int, name: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            roleId: RoleId::from((int) $primitives['role_id']),
            name: RoleName::from($primitives['name']),
        );
    }
}
