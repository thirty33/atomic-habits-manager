<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\StringEnum;

/**
 * Lifecycle status of a manual crypto payment:
 * pending → payment_notified → confirmed | rejected.
 */
final class PaymentStatus extends StringEnum
{
    public const PENDING = 'pending';

    public const PAYMENT_NOTIFIED = 'payment_notified';

    public const CONFIRMED = 'confirmed';

    public const REJECTED = 'rejected';

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function paymentNotified(): self
    {
        return new self(self::PAYMENT_NOTIFIED);
    }

    public static function confirmed(): self
    {
        return new self(self::CONFIRMED);
    }

    public static function rejected(): self
    {
        return new self(self::REJECTED);
    }

    public function isPaymentNotified(): bool
    {
        return $this->value === self::PAYMENT_NOTIFIED;
    }

    public function isConfirmed(): bool
    {
        return $this->value === self::CONFIRMED;
    }

    public function isRejected(): bool
    {
        return $this->value === self::REJECTED;
    }

    /**
     * @return array<int, string>
     */
    protected function allowedValues(): array
    {
        return [self::PENDING, self::PAYMENT_NOTIFIED, self::CONFIRMED, self::REJECTED];
    }
}
