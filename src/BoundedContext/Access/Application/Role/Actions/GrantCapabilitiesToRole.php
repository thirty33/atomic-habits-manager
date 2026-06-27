<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Role\Actions;

use Core\BoundedContext\Access\Application\Permission\PermissionReader;
use Core\BoundedContext\Access\Application\Role\DTOs\GrantCapabilitiesData;
use Core\BoundedContext\Access\Domain\Role\Exceptions\UnknownPermissions;
use Core\BoundedContext\Access\Domain\Role\RoleRepository;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\PermissionIdCollection;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * Grants capabilities to a role additively (keeps the ones it already has).
 * Used by the catalog seeder; the UI uses UpdateRole (replace) instead.
 */
final readonly class GrantCapabilitiesToRole
{
    public function __construct(
        private RoleRepository $roles,
        private PermissionReader $permissions,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(GrantCapabilitiesData $data): void
    {
        if ($data->permissionIds !== []) {
            $existing = $this->permissions->existingIdsAmong($data->permissionIds);
            $unknown = array_values(array_diff($data->permissionIds, $existing));

            if ($unknown !== []) {
                throw UnknownPermissions::withIds($unknown);
            }
        }

        $role = $this->roles->find(RoleId::from($data->roleId));
        $role->grant(PermissionIdCollection::fromIds($data->permissionIds));

        $this->transaction->execute(fn () => $this->roles->save($role));
    }
}
