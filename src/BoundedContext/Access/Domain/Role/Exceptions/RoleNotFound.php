<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role\Exceptions;

use Core\BoundedContext\Access\Domain\Role\ValueObjects\RoleId;
use Core\Shared\Domain\DomainException;

final class RoleNotFound extends DomainException
{
    public static function withId(RoleId $id): self
    {
        return new self(sprintf('Role %d not found.', $id->value()));
    }
}
