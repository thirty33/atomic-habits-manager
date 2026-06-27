<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module;

use Core\BoundedContext\Access\Domain\Module\Events\ModuleWasCreated;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleCode;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleName;
use Core\Shared\Domain\AggregateRoot;
use LogicException;

/**
 * Aggregate Root of a module: the grouping a capability permission belongs to
 * (habits, calendar, reports, backoffice, ...). Pure domain: no Eloquent.
 * Persistence is a Data Mapper (ModuleRepository).
 */
final class Module extends AggregateRoot
{
    private function __construct(
        private ?ModuleId $id,
        private ModuleCode $code,
        private ModuleName $name,
        private bool $isActive,
    ) {}

    public static function create(ModuleCode $code, ModuleName $name, bool $isActive = true): self
    {
        return new self(id: null, code: $code, name: $name, isActive: $isActive);
    }

    public static function fromPrimitives(ModuleId $id, ModuleCode $code, ModuleName $name, bool $isActive): self
    {
        return new self(id: $id, code: $code, name: $name, isActive: $isActive);
    }

    public function update(ModuleName $name, bool $isActive): void
    {
        $this->name = $name;
        $this->isActive = $isActive;
    }

    public function assignId(ModuleId $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Module already has an id.');
        }

        $this->id = $id;
    }

    /**
     * Records the "module created" event AFTER the repository has assigned the
     * DB-generated id. Deliberate pattern, not a leak: with auto-increment PKs the
     * id does not exist at factory time, so the creation event cannot be recorded
     * inside create(). The repository calls this once, right after assignId().
     */
    public function recordCreatedAfterAssign(): void
    {
        $this->record(ModuleWasCreated::now($this->id(), $this->code));
    }

    public function id(): ModuleId
    {
        return $this->id ?? throw new LogicException('Module has not been persisted yet.');
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function code(): ModuleCode
    {
        return $this->code;
    }

    public function name(): ModuleName
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
