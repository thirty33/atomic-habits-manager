<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Access\Domain;

use Core\BoundedContext\Access\Domain\Module\Exceptions\InvalidModuleCode;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleCode;
use Core\BoundedContext\Access\Domain\Permission\Exceptions\InvalidPermissionCode;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionCode;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\PermissionIdCollection;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleName;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    public function test_module_code_normalizes_and_validates(): void
    {
        $code = ModuleCode::from('  Habits  ');

        $this->assertSame('habits', $code->value());
    }

    public function test_module_code_rejects_invalid_format(): void
    {
        $this->expectException(InvalidModuleCode::class);

        ModuleCode::from('Habits.View');
    }

    public function test_permission_code_requires_namespace(): void
    {
        $this->assertSame('habits.create', PermissionCode::from('Habits.Create')->value());

        $this->expectException(InvalidPermissionCode::class);

        PermissionCode::from('habits');
    }

    public function test_role_name_trims_and_bounds(): void
    {
        $this->assertSame('Admin', RoleName::from('  Admin ')->value());

        $this->expectException(\InvalidArgumentException::class);

        RoleName::from(str_repeat('a', 201));
    }

    public function test_permission_id_collection_normalizes_unique_sorted(): void
    {
        $collection = PermissionIdCollection::fromIds([3, 1, 3, 2]);

        $this->assertSame([1, 2, 3], $collection->toArray());
        $this->assertCount(3, $collection);
    }

    public function test_permission_id_collection_holds_permission_id_value_objects(): void
    {
        $collection = PermissionIdCollection::fromIds([5]);

        $this->assertInstanceOf(PermissionId::class, $collection->items()[0]);
    }

    public function test_permission_id_collection_merge_is_immutable_and_deduplicates(): void
    {
        $a = PermissionIdCollection::fromIds([1, 2]);
        $b = PermissionIdCollection::fromIds([2, 3]);

        $merged = $a->merge($b);

        $this->assertSame([1, 2, 3], $merged->toArray());
        $this->assertSame([1, 2], $a->toArray());
    }
}
