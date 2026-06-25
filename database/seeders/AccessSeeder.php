<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Core\BoundedContext\Access\Application\Module\Actions\CreateModule;
use Core\BoundedContext\Access\Application\Module\DTOs\CreateModuleData;
use Core\BoundedContext\Access\Application\Module\ModuleReader;
use Core\BoundedContext\Access\Application\Permission\Actions\CreatePermission;
use Core\BoundedContext\Access\Application\Permission\DTOs\CreatePermissionData;
use Core\BoundedContext\Access\Application\Permission\PermissionReader;
use Core\BoundedContext\Access\Application\Role\Actions\CreateRole;
use Core\BoundedContext\Access\Application\Role\Actions\GrantCapabilitiesToRole;
use Core\BoundedContext\Access\Application\Role\DTOs\CreateRoleData;
use Core\BoundedContext\Access\Application\Role\DTOs\GrantCapabilitiesData;
use Core\BoundedContext\Access\Application\Role\RoleReader;
use Core\BoundedContext\Access\Domain\Permission\Capability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Provisions the Access catalog: the application modules, one capability
 * permission per Capability enum case, a superadmin role holding the
 * BackofficeAdmin capability, and assigns that role to every existing admin
 * (is_admin = true) user. Idempotent: re-running it creates nothing twice.
 */
class AccessSeeder extends Seeder
{
    public const SUPERADMIN_ROLE = 'Superadmin';

    public function run(
        CreateModule $createModule,
        CreatePermission $createPermission,
        CreateRole $createRole,
        GrantCapabilitiesToRole $grantCapabilities,
        ModuleReader $modules,
        PermissionReader $permissions,
        RoleReader $roles,
    ): void {
        $this->seedModules($createModule, $modules);
        $this->seedPermissions($createPermission, $modules, $permissions);
        $roleId = $this->seedSuperadminRole($createRole, $roles);
        $this->grantBackofficeAdmin($grantCapabilities, $permissions, $roleId);
        $this->assignRoleToAdmins($roleId);
    }

    private function seedModules(CreateModule $createModule, ModuleReader $modules): void
    {
        foreach ($this->moduleCodes() as $code) {
            if ($modules->findIdByCode($code) !== null) {
                continue;
            }

            $createModule(new CreateModuleData(
                code: $code,
                name: $this->humanize($code),
                isActive: true,
            ));
        }
    }

    private function seedPermissions(
        CreatePermission $createPermission,
        ModuleReader $modules,
        PermissionReader $permissions,
    ): void {
        foreach (Capability::cases() as $capability) {
            if ($permissions->findIdByCode($capability->value) !== null) {
                continue;
            }

            $moduleId = $modules->findIdByCode($capability->moduleCode());

            if ($moduleId === null) {
                continue;
            }

            $createPermission(new CreatePermissionData(
                name: $this->humanizeCapability($capability),
                moduleId: $moduleId,
                code: $capability->value,
            ));
        }
    }

    private function seedSuperadminRole(CreateRole $createRole, RoleReader $roles): int
    {
        $existing = $roles->findIdByName(self::SUPERADMIN_ROLE);

        if ($existing !== null) {
            return $existing;
        }

        return $createRole(new CreateRoleData(
            name: self::SUPERADMIN_ROLE,
            permissionIds: [],
        ));
    }

    private function grantBackofficeAdmin(
        GrantCapabilitiesToRole $grantCapabilities,
        PermissionReader $permissions,
        int $roleId,
    ): void {
        $permissionId = $permissions->findIdByCode(Capability::BackofficeAdmin->value);

        if ($permissionId === null) {
            return;
        }

        $grantCapabilities(new GrantCapabilitiesData(
            roleId: $roleId,
            permissionIds: [$permissionId],
        ));
    }

    private function assignRoleToAdmins(int $roleId): void
    {
        User::query()->where('is_admin', true)->get()->each(
            static fn (User $user) => $user->roles()->syncWithoutDetaching([$roleId]),
        );
    }

    /**
     * @return list<string>
     */
    private function moduleCodes(): array
    {
        $codes = array_map(
            static fn (Capability $capability): string => $capability->moduleCode(),
            Capability::cases(),
        );

        return array_values(array_unique($codes));
    }

    private function humanizeCapability(Capability $capability): string
    {
        return Str::headline($capability->name);
    }

    private function humanize(string $code): string
    {
        return Str::headline($code);
    }
}
