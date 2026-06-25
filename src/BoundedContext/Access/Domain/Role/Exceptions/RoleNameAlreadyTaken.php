<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role\Exceptions;

use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleName;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

final class RoleNameAlreadyTaken extends DomainException implements ProvidesValidationErrors
{
    public static function forName(RoleName $name): self
    {
        return new self(sprintf('Role name "%s" is already taken.', $name->value()));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['name' => ['El nombre del rol ya está en uso.']];
    }
}
