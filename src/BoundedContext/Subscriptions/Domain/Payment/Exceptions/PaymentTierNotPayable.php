<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Raised when a payment is notified for a non-payable tier (e.g. free, amount 0).
 * Only a paid plan may be notified. Implementing ProvidesValidationErrors makes
 * the single render() in bootstrap/app.php surface it as a clean 422 on
 * `plan_tier`, matching the project's "domain validation -> 422 by contract"
 * convention.
 */
final class PaymentTierNotPayable extends DomainException implements ProvidesValidationErrors
{
    public static function forTier(PlanTier $tier): self
    {
        return new self(sprintf('Tier "%s" is not payable.', $tier->value()));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['plan_tier' => [__('Solo puedes pagar un plan de pago.')]];
    }
}
