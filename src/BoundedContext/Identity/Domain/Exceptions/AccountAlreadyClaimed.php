<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Exceptions;

use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Raised when a claim is attempted on a user that is no longer a guest (it has
 * already been registered). Guards against re-POSTing the registration form
 * silently overwriting an existing account's name/email/password. Implementing
 * ProvidesValidationErrors makes the single render() in bootstrap/app.php
 * surface it as a clean 422 field error on `email`, matching the project's
 * "domain validation -> 422 by contract" convention.
 */
final class AccountAlreadyClaimed extends DomainException implements ProvidesValidationErrors
{
    public static function create(): self
    {
        return new self('This account has already been registered.');
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['email' => [__('Esta cuenta ya está registrada.')]];
    }
}
