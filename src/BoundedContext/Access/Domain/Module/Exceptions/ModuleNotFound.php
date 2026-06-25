<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module\Exceptions;

use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\Shared\Domain\DomainException;

final class ModuleNotFound extends DomainException
{
    public static function withId(ModuleId $id): self
    {
        return new self(sprintf('Module %d not found.', $id->value()));
    }
}
