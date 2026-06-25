<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role\Exceptions;

use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Thrown when a role is asked to reference permission ids that do not exist.
 */
final class UnknownPermissions extends DomainException implements ProvidesValidationErrors
{
    /**
     * @param  list<int>  $ids
     */
    public static function withIds(array $ids): self
    {
        return new self(sprintf('Unknown permission ids: %s.', implode(', ', $ids)));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['permissions' => ['Uno o más permisos seleccionados no existen.']];
    }
}
