<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role;

use Core\BoundedContext\Access\Domain\Role\Exceptions\RoleNotFound;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;

/**
 * Write-side contract for roles. Persists the name and syncs the
 * permission_role pivot. Bare DB operations only — the transaction boundary
 * lives in the Application use case (TransactionManager), never here.
 */
interface RoleRepository
{
    public function save(Role $role): void;

    /** @throws RoleNotFound */
    public function find(RoleId $id): Role;

    public function deleteById(RoleId $id): void;
}
