<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Policy\Exceptions;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\Shared\Domain\DomainException;

/**
 * Raised when a plan tier may not access a given module. Consumers (route
 * middleware) translate this to a 403.
 */
final class ModuleNotAllowed extends DomainException
{
    public static function for(PlanTier $tier, string $moduleCode): self
    {
        return new self(sprintf('Plan "%s" cannot access module "%s".', $tier->value(), $moduleCode));
    }
}
