<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Exceptions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Email uniqueness is a domain rule (checked in the use case via the
 * repository). Implementing ProvidesValidationErrors makes the single render()
 * in bootstrap/app.php surface it as a 422 field error on `email`, matching the
 * project's "domain validation -> 422 by contract" convention.
 */
final class EmailAlreadyTaken extends DomainException implements ProvidesValidationErrors
{
    public static function for(EmailAddress $email): self
    {
        return new self(sprintf('Email %s is already taken.', $email->value()));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['email' => [__('Este correo ya está registrado.')]];
    }
}
