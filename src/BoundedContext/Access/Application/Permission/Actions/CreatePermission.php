<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Permission\Actions;

use Core\BoundedContext\Access\Application\Permission\DTOs\CreatePermissionData;
use Core\BoundedContext\Access\Application\Permission\PermissionReader;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\BoundedContext\Access\Domain\Permission\Exceptions\PermissionCodeAlreadyTaken;
use Core\BoundedContext\Access\Domain\Permission\Permission;
use Core\BoundedContext\Access\Domain\Permission\PermissionRepository;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionCode;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionName;
use Core\Shared\Application\Persistence\TransactionManager;

final readonly class CreatePermission
{
    public function __construct(
        private PermissionRepository $repository,
        private PermissionReader $permissions,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(CreatePermissionData $data): int
    {
        $code = PermissionCode::from($data->code);

        if ($this->permissions->findIdByCode($code->value()) !== null) {
            throw PermissionCodeAlreadyTaken::forCode($code);
        }

        $permission = Permission::capability(
            name: PermissionName::from($data->name),
            moduleId: ModuleId::from($data->moduleId),
            code: $code,
        );

        $this->transaction->execute(fn () => $this->repository->save($permission));

        return $permission->id()->value();
    }
}
