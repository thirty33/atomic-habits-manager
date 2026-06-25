<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Permission;

/**
 * Read-side port for permissions (CQRS counterpart of PermissionRepository).
 */
interface PermissionReader
{
    public function findIdByCode(string $code): ?int;

    /**
     * Capability permissions as select options, ordered by module then name.
     *
     * @return array<int, string> id => name
     */
    public function capabilityOptions(): array;

    /**
     * Subset of the given ids that actually exist as permissions. Used by the
     * role use cases to reject references to permissions that do not exist.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    public function existingIdsAmong(array $ids): array;
}
