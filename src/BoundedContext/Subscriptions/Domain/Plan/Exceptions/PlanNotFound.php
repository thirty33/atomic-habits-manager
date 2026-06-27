<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Plan\Exceptions;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Raised when the requested plan/tier has no catalog row. Implementing
 * ProvidesValidationErrors makes the single render() in bootstrap/app.php
 * surface it as a clean 422 on `plan_tier` instead of leaking a 500 with the
 * exception class and file path, matching the project's "domain validation ->
 * 422 by contract" convention.
 */
final class PlanNotFound extends DomainException implements ProvidesValidationErrors
{
    public static function forTier(PlanTier $tier): self
    {
        return new self(sprintf('Plan for tier "%s" not found.', $tier->value()));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['plan_tier' => [__('El plan solicitado no está disponible.')]];
    }
}
