<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent;

use App\Models\Permission as PermissionModel;
use Core\BoundedContext\Access\Application\Permission\PermissionReader;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\BoundedContext\Access\Domain\Permission\Exceptions\PermissionNotFound;
use Core\BoundedContext\Access\Domain\Permission\Permission;
use Core\BoundedContext\Access\Domain\Permission\PermissionRepository;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionCode;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionName;
use Core\Shared\Domain\Bus\DomainEventBus;

/**
 * Data Mapper between the Permission aggregate and App\Models\Permission.
 * Implements write-side (PermissionRepository) and read-side (PermissionReader).
 */
final readonly class EloquentPermissionRepository implements PermissionReader, PermissionRepository
{
    public function __construct(private DomainEventBus $bus) {}

    public function save(Permission $permission): void
    {
        $model = $permission->hasId()
            ? PermissionModel::query()->findOrFail($permission->id()->value())
            : new PermissionModel;

        $model->fill([
            'name' => $permission->name()->value(),
            'module_id' => $permission->moduleId()->value(),
            'code' => $permission->code()->value(),
        ])->save();

        if (! $permission->hasId()) {
            $permission->assignId(PermissionId::from((int) $model->getKey()));
            $permission->recordCreatedAfterAssign();
        }

        $this->bus->publish(...$permission->pullDomainEvents());
    }

    public function find(PermissionId $id): Permission
    {
        $model = PermissionModel::query()->find($id->value());

        if ($model === null) {
            throw PermissionNotFound::withId($id);
        }

        return Permission::fromPrimitives(
            id: PermissionId::from((int) $model->getKey()),
            name: PermissionName::from((string) $model->name),
            moduleId: ModuleId::from((int) $model->module_id),
            code: PermissionCode::from((string) $model->code),
        );
    }

    public function findIdByCode(string $code): ?int
    {
        $id = PermissionModel::query()->where('code', $code)->value('permission_id');

        return $id !== null ? (int) $id : null;
    }

    public function capabilityOptions(): array
    {
        return PermissionModel::query()
            ->orderBy('module_id')
            ->orderBy('name')
            ->pluck('name', 'permission_id')
            ->all();
    }

    public function existingIdsAmong(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return PermissionModel::query()
            ->whereIn('permission_id', $ids)
            ->pluck('permission_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function deleteById(PermissionId $id): void
    {
        $model = PermissionModel::query()->find($id->value());

        if ($model === null) {
            return;
        }

        $model->roles()->detach();
        $model->delete();
    }
}
