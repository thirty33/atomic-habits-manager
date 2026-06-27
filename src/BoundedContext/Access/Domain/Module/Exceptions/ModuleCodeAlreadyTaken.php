<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module\Exceptions;

use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleCode;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

final class ModuleCodeAlreadyTaken extends DomainException implements ProvidesValidationErrors
{
    public static function forCode(ModuleCode $code): self
    {
        return new self(sprintf('Module code "%s" is already taken.', $code->value()));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['code' => ['El código de módulo ya está en uso.']];
    }
}
