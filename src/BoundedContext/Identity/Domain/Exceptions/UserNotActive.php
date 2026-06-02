<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Exceptions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\DomainException;

final class UserNotActive extends DomainException
{
    public static function withId(?UserId $id): self
    {
        return new self(sprintf('User %d is not active.', $id?->value() ?? 0));
    }
}
