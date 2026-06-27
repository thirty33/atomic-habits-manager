<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission;

use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\BoundedContext\Access\Domain\Permission\Events\PermissionWasCreated;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionCode;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionName;
use Core\Shared\Domain\AggregateRoot;
use LogicException;

/**
 * Aggregate Root of a permission. In this application a permission is ALWAYS a
 * capability: it belongs to a module AND has a namespaced code (e.g.
 * "habits.create"). The DELICIUS "segment" nature has been dropped. Pure
 * domain: no Eloquent.
 */
final class Permission extends AggregateRoot
{
    private function __construct(
        private ?PermissionId $id,
        private PermissionName $name,
        private ModuleId $moduleId,
        private PermissionCode $code,
    ) {}

    public static function capability(PermissionName $name, ModuleId $moduleId, PermissionCode $code): self
    {
        return new self(id: null, name: $name, moduleId: $moduleId, code: $code);
    }

    public static function fromPrimitives(
        PermissionId $id,
        PermissionName $name,
        ModuleId $moduleId,
        PermissionCode $code,
    ): self {
        return new self(id: $id, name: $name, moduleId: $moduleId, code: $code);
    }

    public function rename(PermissionName $name): void
    {
        $this->name = $name;
    }

    public function assignId(PermissionId $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Permission already has an id.');
        }

        $this->id = $id;
    }

    /**
     * Records the "permission created" event AFTER the repository has assigned the
     * DB-generated id. Deliberate pattern, not a leak: with auto-increment PKs the
     * id does not exist at factory time, so the creation event cannot be recorded
     * inside create(). The repository calls this once, right after assignId().
     */
    public function recordCreatedAfterAssign(): void
    {
        $this->record(PermissionWasCreated::now($this->id(), $this->code));
    }

    public function id(): PermissionId
    {
        return $this->id ?? throw new LogicException('Permission has not been persisted yet.');
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function name(): PermissionName
    {
        return $this->name;
    }

    public function moduleId(): ModuleId
    {
        return $this->moduleId;
    }

    public function code(): PermissionCode
    {
        return $this->code;
    }
}
