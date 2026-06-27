<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Role\Actions;

use Core\BoundedContext\Access\Application\Permission\PermissionReader;
use Core\BoundedContext\Access\Application\Role\DTOs\UpdateRoleData;
use Core\BoundedContext\Access\Application\Role\RoleReader;
use Core\BoundedContext\Access\Domain\Role\Exceptions\RoleNameAlreadyTaken;
use Core\BoundedContext\Access\Domain\Role\Exceptions\UnknownPermissions;
use Core\BoundedContext\Access\Domain\Role\RoleRepository;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\PermissionIdCollection;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleName;
use Core\Shared\Application\Persistence\TransactionManager;

final readonly class UpdateRole
{
    public function __construct(
        private RoleRepository $repository,
        private RoleReader $roles,
        private PermissionReader $permissions,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(UpdateRoleData $data): void
    {
        $name = RoleName::from($data->name);

        $existingId = $this->roles->findIdByName($name->value());
        if ($existingId !== null && $existingId !== $data->id) {
            throw RoleNameAlreadyTaken::forName($name);
        }

        $this->assertPermissionsExist($data->permissionIds);

        $role = $this->repository->find(RoleId::from($data->id));
        $role->rename($name);
        $role->replaceCapabilities(PermissionIdCollection::fromIds($data->permissionIds));

        $this->transaction->execute(fn () => $this->repository->save($role));
    }

    /**
     * @param  list<int>  $permissionIds
     */
    private function assertPermissionsExist(array $permissionIds): void
    {
        if ($permissionIds === []) {
            return;
        }

        $existing = $this->permissions->existingIdsAmong($permissionIds);
        $unknown = array_values(array_diff($permissionIds, $existing));

        if ($unknown !== []) {
            throw UnknownPermissions::withIds($unknown);
        }
    }
}
