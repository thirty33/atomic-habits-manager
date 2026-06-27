<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Role;

/**
 * Read-side port for roles (CQRS counterpart of RoleRepository).
 */
interface RoleReader
{
    /** @return array<int, string>  id => name */
    public function options(): array;

    /** @return list<int>  capability permission ids granted to the role */
    public function permissionIdsOf(int $roleId): array;

    public function findIdByName(string $name): ?int;
}
