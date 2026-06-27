<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission\Exceptions;

use Core\BoundedContext\Access\Domain\Permission\ValueObjects\PermissionId;
use Core\Shared\Domain\DomainException;

final class PermissionNotFound extends DomainException
{
    public static function withId(PermissionId $id): self
    {
        return new self(sprintf('Permission %d not found.', $id->value()));
    }
}
