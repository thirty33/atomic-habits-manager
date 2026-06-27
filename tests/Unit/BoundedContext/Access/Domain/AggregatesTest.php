<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Access\Domain;

use Core\BoundedContext\Access\Domain\Module\Events\ModuleWasCreated;
use Core\BoundedContext\Access\Domain\Module\Module;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleCode;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleName;
use Core\BoundedContext\Access\Domain\Permission\Permission;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionCode;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionName;
use Core\BoundedContext\Access\Domain\Role\Events\RoleWasCreated;
use Core\BoundedContext\Access\Domain\Role\Role;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\PermissionIdCollection;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleName;
use LogicException;
use PHPUnit\Framework\TestCase;

class AggregatesTest extends TestCase
{
    public function test_new_role_has_no_id_until_assigned(): void
    {
        $role = Role::create(RoleName::from('Admin'), PermissionIdCollection::empty());

        $this->assertFalse($role->hasId());

        $role->assignId(RoleId::from(7));

        $this->assertTrue($role->hasId());
        $this->assertSame(7, $role->id()->value());
    }

    public function test_role_records_created_event_only_after_id_assigned(): void
    {
        $role = Role::create(RoleName::from('Admin'), PermissionIdCollection::empty());
        $this->assertSame([], $role->peekDomainEvents());

        $role->assignId(RoleId::from(1));
        $role->recordCreatedAfterAssign();

        $events = $role->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleWasCreated::class, $events[0]);
        $this->assertSame([], $role->peekDomainEvents());
    }

    public function test_role_cannot_be_assigned_id_twice(): void
    {
        $role = Role::create(RoleName::from('Admin'), PermissionIdCollection::empty());
        $role->assignId(RoleId::from(1));

        $this->expectException(LogicException::class);
        $role->assignId(RoleId::from(2));
    }

    public function test_role_replace_capabilities_swaps_the_whole_set(): void
    {
        $role = Role::create(RoleName::from('Admin'), PermissionIdCollection::fromIds([1, 2]));
        $role->replaceCapabilities(PermissionIdCollection::fromIds([9]));

        $this->assertSame([9], $role->permissions()->toArray());
    }

    public function test_role_grant_is_additive(): void
    {
        $role = Role::create(RoleName::from('Admin'), PermissionIdCollection::fromIds([1]));
        $role->grant(PermissionIdCollection::fromIds([2, 1]));

        $this->assertSame([1, 2], $role->permissions()->toArray());
    }

    public function test_permission_capability_binds_a_module_and_code(): void
    {
        $permission = Permission::capability(
            PermissionName::from('Create Habits'),
            ModuleId::from(3),
            PermissionCode::from('habits.create'),
        );

        $this->assertSame(3, $permission->moduleId()->value());
        $this->assertSame('habits.create', $permission->code()->value());
    }

    public function test_module_records_created_event_after_assign(): void
    {
        $module = Module::create(ModuleCode::from('habits'), ModuleName::from('Habits'));
        $module->assignId(ModuleId::from(4));
        $module->recordCreatedAfterAssign();

        $events = $module->pullDomainEvents();
        $this->assertInstanceOf(ModuleWasCreated::class, $events[0]);
    }

    public function test_module_update_changes_name_and_active_flag(): void
    {
        $module = Module::create(ModuleCode::from('habits'), ModuleName::from('Habits'), true);
        $module->update(ModuleName::from('Hábitos'), false);

        $this->assertSame('Hábitos', $module->name()->value());
        $this->assertFalse($module->isActive());
        $this->assertSame('habits', $module->code()->value());
    }
}
