<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role\ValueObjects;

use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\Shared\Domain\Collection;

/**
 * Immutable, typed set of capability permission ids granted to a role (the
 * permission_role pivot). Extends the project's typed Collection base; items are
 * PermissionId value objects. Normalizes to a unique, sorted set.
 */
final class PermissionIdCollection extends Collection
{
    protected function type(): string
    {
        return PermissionId::class;
    }

    /**
     * @param  list<int>  $ids
     */
    public static function fromIds(array $ids): self
    {
        $unique = array_values(array_unique(array_map('intval', $ids)));
        sort($unique);

        return new self(array_map(
            static fn (int $id): PermissionId => PermissionId::from($id),
            $unique,
        ));
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function merge(self $other): self
    {
        return self::fromIds([...$this->toArray(), ...$other->toArray()]);
    }

    /**
     * @return list<int>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (PermissionId $id): int => $id->value(),
            $this->items,
        );
    }
}
