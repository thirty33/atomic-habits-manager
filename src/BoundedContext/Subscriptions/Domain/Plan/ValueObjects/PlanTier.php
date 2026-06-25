<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\StringEnum;

/**
 * The subscription tier a user can be on. Single source of truth for the tier
 * strings; the domain policies (PlanLimits/PlanModules) branch on these.
 */
final class PlanTier extends StringEnum
{
    public const FREE = 'free';

    public const UNLIMITED = 'unlimited';

    public static function free(): self
    {
        return new self(self::FREE);
    }

    public static function unlimited(): self
    {
        return new self(self::UNLIMITED);
    }

    public function isFree(): bool
    {
        return $this->value === self::FREE;
    }

    public function isUnlimited(): bool
    {
        return $this->value === self::UNLIMITED;
    }

    /**
     * @return array<int, string>
     */
    protected function allowedValues(): array
    {
        return [self::FREE, self::UNLIMITED];
    }
}
