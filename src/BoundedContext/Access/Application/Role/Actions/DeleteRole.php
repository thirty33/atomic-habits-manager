<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Role\Actions;

use Core\BoundedContext\Access\Domain\Role\RoleRepository;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\Shared\Application\Persistence\TransactionManager;

final readonly class DeleteRole
{
    public function __construct(
        private RoleRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(int $roleId): void
    {
        $this->transaction->execute(function () use ($roleId): void {
            $this->repository->deleteById(RoleId::from($roleId));
        });
    }
}
