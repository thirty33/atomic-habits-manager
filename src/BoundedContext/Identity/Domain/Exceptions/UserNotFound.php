<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Exceptions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\DomainException;

final class UserNotFound extends DomainException
{
    public static function withId(UserId $id): self
    {
        return new self(sprintf('User %d not found.', $id->value()));
    }

    public static function withEmail(EmailAddress $email): self
    {
        return new self(sprintf('User with email %s not found.', $email->value()));
    }
}
