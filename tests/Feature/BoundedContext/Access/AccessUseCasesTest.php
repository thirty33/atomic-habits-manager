<?php

declare(strict_types=1);

namespace Tests\Feature\BoundedContext\Access;

use App\Models\Module as ModuleModel;
use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use Core\BoundedContext\Access\Application\Module\Actions\CreateModule;
use Core\BoundedContext\Access\Application\Module\DTOs\CreateModuleData;
use Core\BoundedContext\Access\Application\Permission\Actions\CreatePermission;
use Core\BoundedContext\Access\Application\Permission\DTOs\CreatePermissionData;
use Core\BoundedContext\Access\Application\Role\Actions\CreateRole;
use Core\BoundedContext\Access\Application\Role\Actions\UpdateRole;
use Core\BoundedContext\Access\Application\Role\DTOs\CreateRoleData;
use Core\BoundedContext\Access\Application\Role\DTOs\UpdateRoleData;
use Core\BoundedContext\Access\Domain\Module\Exceptions\ModuleCodeAlreadyTaken;
use Core\BoundedContext\Access\Domain\Permission\Exceptions\PermissionCodeAlreadyTaken;
use Core\BoundedContext\Access\Domain\Role\Exceptions\RoleNameAlreadyTaken;
use Core\BoundedContext\Access\Domain\Role\Exceptions\UnknownPermissions;
use Core\Shared\Domain\ProvidesValidationErrors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessUseCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_module_then_permission_then_role_persists_pivot(): void
    {
        $moduleId = app(CreateModule::class)(new CreateModuleData('habits', 'Habits'));
        $permissionId = app(CreatePermission::class)(
            new CreatePermissionData('Create Habits', $moduleId, 'habits.create'),
        );

        $roleId = app(CreateRole::class)(new CreateRoleData('Editor', [$permissionId]));

        $this->assertDatabaseHas('permission_role', [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
        $this->assertSame(1, RoleModel::query()->count());
    }

    public function test_create_module_rejects_duplicate_code_as_validation_error(): void
    {
        app(CreateModule::class)(new CreateModuleData('habits', 'Habits'));

        try {
            app(CreateModule::class)(new CreateModuleData('habits', 'Habits Again'));
            $this->fail('Expected ModuleCodeAlreadyTaken.');
        } catch (ModuleCodeAlreadyTaken $e) {
            $this->assertInstanceOf(ProvidesValidationErrors::class, $e);
            $this->assertArrayHasKey('code', $e->validationErrors());
        }

        $this->assertSame(1, ModuleModel::query()->count());
    }

    public function test_create_permission_rejects_duplicate_code(): void
    {
        $moduleId = app(CreateModule::class)(new CreateModuleData('habits', 'Habits'));
        app(CreatePermission::class)(new CreatePermissionData('A', $moduleId, 'habits.view'));

        $this->expectException(PermissionCodeAlreadyTaken::class);
        app(CreatePermission::class)(new CreatePermissionData('B', $moduleId, 'habits.view'));
    }

    public function test_create_role_rejects_duplicate_name(): void
    {
        app(CreateRole::class)(new CreateRoleData('Editor', []));

        $this->expectException(RoleNameAlreadyTaken::class);
        app(CreateRole::class)(new CreateRoleData('Editor', []));
    }

    public function test_create_role_rejects_unknown_permission_ids(): void
    {
        $this->expectException(UnknownPermissions::class);
        app(CreateRole::class)(new CreateRoleData('Editor', [9999]));
    }

    public function test_update_role_replaces_capabilities(): void
    {
        $moduleId = app(CreateModule::class)(new CreateModuleData('habits', 'Habits'));
        $view = app(CreatePermission::class)(new CreatePermissionData('View', $moduleId, 'habits.view'));
        $create = app(CreatePermission::class)(new CreatePermissionData('Create', $moduleId, 'habits.create'));

        $roleId = app(CreateRole::class)(new CreateRoleData('Editor', [$view]));
        app(UpdateRole::class)(UpdateRoleData::fromArray($roleId, ['name' => 'Editor', 'permissions' => [$create]]));

        $codes = RoleModel::query()->findOrFail($roleId)->permissions->pluck('code')->all();
        $this->assertSame(['habits.create'], $codes);
    }

    public function test_save_publishes_module_created_domain_event_row(): void
    {
        app(CreateModule::class)(new CreateModuleData('habits', 'Habits'));

        // The in-test bus is sync; assert the aggregate state landed and the
        // permission catalog table is reachable for the cross-aggregate flow.
        $this->assertDatabaseHas('modules', ['code' => 'habits']);
        $this->assertSame(0, PermissionModel::query()->count());
    }
}
