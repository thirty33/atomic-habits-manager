<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Exceptions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\Shared\Domain\DomainException;

final class EmailAlreadyTaken extends DomainException
{
    public static function for(EmailAddress $email): self
    {
        return new self(sprintf('Email %s is already taken.', $email->value()));
    }
}
