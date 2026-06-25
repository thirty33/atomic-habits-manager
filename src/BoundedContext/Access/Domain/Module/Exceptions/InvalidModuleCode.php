<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module\Exceptions;

use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Thrown when a raw module code does not match the required machine format
 * (lower-case word, e.g. "habits"). Mapped to a 422 response.
 */
final class InvalidModuleCode extends DomainException implements ProvidesValidationErrors
{
    public static function for(string $raw): self
    {
        return new self(sprintf('Invalid module code: "%s".', $raw));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['code' => ['El código de módulo debe ser minúsculas (letras, números y guion bajo), ej: habits.']];
    }
}
