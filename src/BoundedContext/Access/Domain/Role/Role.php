<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role;

use Core\BoundedContext\Access\Domain\Role\Events\RoleWasCreated;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\PermissionIdCollection;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleName;
use Core\Shared\Domain\AggregateRoot;
use LogicException;

/**
 * Aggregate Root of a security role. Holds its name and the set of capability
 * permission ids granted to it (the permission_role pivot), referencing the
 * Permission aggregate by identity. Pure domain: no Eloquent.
 */
final class Role extends AggregateRoot
{
    private function __construct(
        private ?RoleId $id,
        private RoleName $name,
        private PermissionIdCollection $permissions,
    ) {}

    public static function create(RoleName $name, PermissionIdCollection $permissions): self
    {
        return new self(id: null, name: $name, permissions: $permissions);
    }

    public static function fromPrimitives(RoleId $id, RoleName $name, PermissionIdCollection $permissions): self
    {
        return new self(id: $id, name: $name, permissions: $permissions);
    }

    public function rename(RoleName $name): void
    {
        $this->name = $name;
    }

    /** Replace the whole capability set (used by the admin UI edit). */
    public function replaceCapabilities(PermissionIdCollection $capabilities): void
    {
        $this->permissions = $capabilities;
    }

    /** Add capabilities keeping the existing ones (additive; used by provisioning). */
    public function grant(PermissionIdCollection $capabilities): void
    {
        $this->permissions = $this->permissions->merge($capabilities);
    }

    public function assignId(RoleId $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Role already has an id.');
        }

        $this->id = $id;
    }

    /**
     * Records the "role created" event AFTER the repository has assigned the
     * DB-generated id. Deliberate pattern, not a leak: with auto-increment PKs the
     * id does not exist at factory time, so the creation event cannot be recorded
     * inside create(). The repository calls this once, right after assignId().
     */
    public function recordCreatedAfterAssign(): void
    {
        $this->record(RoleWasCreated::now($this->id(), $this->name));
    }

    public function id(): RoleId
    {
        return $this->id ?? throw new LogicException('Role has not been persisted yet.');
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function name(): RoleName
    {
        return $this->name;
    }

    public function permissions(): PermissionIdCollection
    {
        return $this->permissions;
    }
}
