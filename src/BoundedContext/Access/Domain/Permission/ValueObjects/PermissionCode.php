<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission\ValueObjects;

use Core\BoundedContext\Access\Domain\Permission\Exceptions\InvalidPermissionCode;
use Core\Shared\Domain\ValueObjects\ValueObject;

/**
 * Namespaced machine code of a capability permission, e.g. "habits.create" or
 * "atomic_ia.use". Lower-case segments joined by dots. Validates format only;
 * the catalog of which codes exist is app data (the seeder + Capability enum),
 * not here.
 */
final class PermissionCode extends ValueObject
{
    protected function __construct(private readonly string $value)
    {
        if (preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $this->value) !== 1) {
            throw InvalidPermissionCode::for($this->value);
        }
    }

    public static function from(mixed ...$values): static
    {
        return new self(mb_strtolower(trim((string) $values[0])));
    }

    public function value(): string
    {
        return $this->value;
    }
}
