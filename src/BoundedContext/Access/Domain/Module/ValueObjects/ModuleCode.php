<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module\ValueObjects;

use Core\BoundedContext\Access\Domain\Module\Exceptions\InvalidModuleCode;
use Core\Shared\Domain\ValueObjects\ValueObject;

/**
 * Machine code of a module, e.g. "habits", "backoffice", "reports". Lower-case
 * snake/single word; validates format, does not enumerate concrete values.
 */
final class ModuleCode extends ValueObject
{
    protected function __construct(private readonly string $value)
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $this->value) !== 1) {
            throw InvalidModuleCode::for($this->value);
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
