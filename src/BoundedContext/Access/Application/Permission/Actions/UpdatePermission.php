<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Permission\Actions;

use Core\BoundedContext\Access\Application\Permission\DTOs\UpdatePermissionData;
use Core\BoundedContext\Access\Domain\Permission\PermissionRepository;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionName;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * Renames a permission. The module and code are catalog data fixed at creation
 * and are NOT editable here on purpose: the code is referenced by application
 * authorization checks.
 */
final readonly class UpdatePermission
{
    public function __construct(
        private PermissionRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(UpdatePermissionData $data): void
    {
        $permission = $this->repository->find(PermissionId::from($data->id));
        $permission->rename(PermissionName::from($data->name));

        $this->transaction->execute(fn () => $this->repository->save($permission));
    }
}
