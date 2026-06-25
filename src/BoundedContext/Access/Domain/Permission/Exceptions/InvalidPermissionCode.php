<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission\Exceptions;

use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Thrown when a raw permission code does not match the required namespaced
 * machine format (e.g. "habits.create"). Mapped to a 422 response.
 */
final class InvalidPermissionCode extends DomainException implements ProvidesValidationErrors
{
    public static function for(string $raw): self
    {
        return new self(sprintf('Invalid permission code: "%s".', $raw));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['code' => ['El código de permiso debe ser namespaced en minúsculas, ej: habits.create.']];
    }
}
