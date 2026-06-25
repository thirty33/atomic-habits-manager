<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Permission\Actions;

use Core\BoundedContext\Access\Domain\Permission\PermissionRepository;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\Shared\Application\Persistence\TransactionManager;

final readonly class DeletePermission
{
    public function __construct(
        private PermissionRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(int $permissionId): void
    {
        $this->transaction->execute(function () use ($permissionId): void {
            $this->repository->deleteById(PermissionId::from($permissionId));
        });
    }
}
