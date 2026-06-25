<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent;

use App\Models\Role as RoleModel;
use Core\BoundedContext\Access\Application\Role\RoleReader;
use Core\BoundedContext\Access\Domain\Role\Exceptions\RoleNotFound;
use Core\BoundedContext\Access\Domain\Role\Role;
use Core\BoundedContext\Access\Domain\Role\RoleRepository;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\PermissionIdCollection;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleName;
use Core\Shared\Domain\Bus\DomainEventBus;

/**
 * Data Mapper between the Role aggregate and App\Models\Role, syncing the
 * permission_role pivot. Implements write-side (RoleRepository) and read-side
 * (RoleReader). The pivot sync touches only permission_role.
 */
final readonly class EloquentRoleRepository implements RoleReader, RoleRepository
{
    public function __construct(private DomainEventBus $bus) {}

    public function save(Role $role): void
    {
        $model = $role->hasId()
            ? RoleModel::query()->findOrFail($role->id()->value())
            : new RoleModel;

        $model->fill(['name' => $role->name()->value()])->save();

        if (! $role->hasId()) {
            $role->assignId(RoleId::from((int) $model->getKey()));
            $role->recordCreatedAfterAssign();
        }

        $model->permissions()->sync($role->permissions()->toArray());

        $this->bus->publish(...$role->pullDomainEvents());
    }

    public function find(RoleId $id): Role
    {
        $model = RoleModel::query()->with('permissions:permission_id')->find($id->value());

        if ($model === null) {
            throw RoleNotFound::withId($id);
        }

        return Role::fromPrimitives(
            id: RoleId::from((int) $model->getKey()),
            name: RoleName::from((string) $model->name),
            permissions: PermissionIdCollection::fromIds(
                $model->permissions->pluck('permission_id')->map(static fn ($id): int => (int) $id)->all(),
            ),
        );
    }

    public function options(): array
    {
        return RoleModel::query()->orderBy('name')->pluck('name', 'role_id')->all();
    }

    public function permissionIdsOf(int $roleId): array
    {
        $model = RoleModel::query()->with('permissions:permission_id')->find($roleId);

        return $model === null
            ? []
            : $model->permissions->pluck('permission_id')->map(static fn ($id): int => (int) $id)->all();
    }

    public function findIdByName(string $name): ?int
    {
        $id = RoleModel::query()->where('name', $name)->value('role_id');

        return $id !== null ? (int) $id : null;
    }

    public function deleteById(RoleId $id): void
    {
        $model = RoleModel::query()->find($id->value());

        if ($model === null) {
            return;
        }

        $model->permissions()->detach();
        $model->users()->detach();
        $model->delete();
    }
}
