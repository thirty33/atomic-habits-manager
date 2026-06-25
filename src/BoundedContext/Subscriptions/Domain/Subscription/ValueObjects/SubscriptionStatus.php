<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\StringEnum;

/**
 * Lifecycle status of a user's current subscription. "active" is the normal
 * state for both free and (confirmed) unlimited; "payment_notified" marks that
 * the user reported a crypto payment that the admin has not yet confirmed.
 */
final class SubscriptionStatus extends StringEnum
{
    public const ACTIVE = 'active';

    public const PAYMENT_NOTIFIED = 'payment_notified';

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function paymentNotified(): self
    {
        return new self(self::PAYMENT_NOTIFIED);
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isPaymentNotified(): bool
    {
        return $this->value === self::PAYMENT_NOTIFIED;
    }

    /**
     * @return array<int, string>
     */
    protected function allowedValues(): array
    {
        return [self::ACTIVE, self::PAYMENT_NOTIFIED];
    }
}
