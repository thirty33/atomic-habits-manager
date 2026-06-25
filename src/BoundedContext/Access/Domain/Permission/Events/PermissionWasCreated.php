<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission\Events;

use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionCode;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class PermissionWasCreated extends DomainEvent
{
    public function __construct(
        public readonly PermissionId $permissionId,
        public readonly PermissionCode $code,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(PermissionId $permissionId, PermissionCode $code): self
    {
        return new self(permissionId: $permissionId, code: $code);
    }

    public static function eventName(): string
    {
        return 'access.permission_was_created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'permission_id' => $this->permissionId->value(),
            'code' => $this->code->value(),
        ];
    }

    /**
     * @param  array{permission_id: int, code: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            permissionId: PermissionId::from((int) $primitives['permission_id']),
            code: PermissionCode::from($primitives['code']),
        );
    }
}
