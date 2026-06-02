<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Exceptions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\DomainException;

final class InvalidCredentials extends DomainException
{
    public static function forEmail(EmailAddress $email): self
    {
        return new self(sprintf('Invalid credentials for %s.', $email->value()));
    }

    public static function forPasswordChange(?UserId $userId): self
    {
        return new self(sprintf('Current password did not match for user %d.', $userId?->value() ?? 0));
    }
}
