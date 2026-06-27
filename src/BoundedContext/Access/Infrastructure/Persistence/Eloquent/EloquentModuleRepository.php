<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent;

use App\Models\Module as ModuleModel;
use Core\BoundedContext\Access\Application\Module\ModuleReader;
use Core\BoundedContext\Access\Domain\Module\Exceptions\ModuleNotFound;
use Core\BoundedContext\Access\Domain\Module\Module;
use Core\BoundedContext\Access\Domain\Module\ModuleRepository;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleCode;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleName;
use Core\Shared\Domain\Bus\DomainEventBus;

/**
 * Data Mapper between the Module aggregate and App\Models\Module. Implements the
 * write-side (ModuleRepository) and read-side (ModuleReader).
 */
final readonly class EloquentModuleRepository implements ModuleReader, ModuleRepository
{
    public function __construct(private DomainEventBus $bus) {}

    public function save(Module $module): void
    {
        $model = $module->hasId()
            ? ModuleModel::query()->findOrFail($module->id()->value())
            : new ModuleModel;

        $model->fill([
            'code' => $module->code()->value(),
            'name' => $module->name()->value(),
            'is_active' => $module->isActive(),
        ])->save();

        if (! $module->hasId()) {
            $module->assignId(ModuleId::from((int) $model->getKey()));
            $module->recordCreatedAfterAssign();
        }

        $this->bus->publish(...$module->pullDomainEvents());
    }

    public function find(ModuleId $id): Module
    {
        $model = ModuleModel::query()->find($id->value());

        if ($model === null) {
            throw ModuleNotFound::withId($id);
        }

        return Module::fromPrimitives(
            id: ModuleId::from((int) $model->getKey()),
            code: ModuleCode::from((string) $model->code),
            name: ModuleName::from((string) $model->name),
            isActive: (bool) $model->is_active,
        );
    }

    public function options(): array
    {
        return ModuleModel::query()->orderBy('name')->pluck('name', 'module_id')->all();
    }

    public function findIdByCode(string $code): ?int
    {
        $id = ModuleModel::query()->where('code', $code)->value('module_id');

        return $id !== null ? (int) $id : null;
    }

    public function deleteById(ModuleId $id): void
    {
        ModuleModel::query()->whereKey($id->value())->delete();
    }
}
