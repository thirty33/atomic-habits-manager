<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission\Exceptions;

use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionCode;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

final class PermissionCodeAlreadyTaken extends DomainException implements ProvidesValidationErrors
{
    public static function forCode(PermissionCode $code): self
    {
        return new self(sprintf('Permission code "%s" is already taken.', $code->value()));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['code' => ['El código de permiso ya está en uso.']];
    }
}
