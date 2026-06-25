<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission;

use Core\BoundedContext\Access\Domain\Permission\Exceptions\PermissionNotFound;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;

/**
 * Write-side contract for permissions. Bare DB operations only — the
 * transaction boundary lives in the Application use case (TransactionManager),
 * never here.
 */
interface PermissionRepository
{
    public function save(Permission $permission): void;

    /** @throws PermissionNotFound */
    public function find(PermissionId $id): Permission;

    public function deleteById(PermissionId $id): void;
}
