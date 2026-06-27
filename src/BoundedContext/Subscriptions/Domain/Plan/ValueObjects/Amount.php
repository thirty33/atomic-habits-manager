<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use InvalidArgumentException;

/**
 * Monetary amount of a plan (the price the user must pay), kept as a
 * non-negative decimal. There is no shared Money value object in this project
 * (checked src/Shared), so Amount + Currency model the price locally.
 */
final class Amount extends ValueObject
{
    protected function __construct(private readonly float $value)
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException(sprintf('Amount must be non-negative, got %s.', $this->value));
        }
    }

    public static function from(mixed ...$values): static
    {
        return new self((float) $values[0]);
    }

    public function value(): float
    {
        return $this->value;
    }
}
