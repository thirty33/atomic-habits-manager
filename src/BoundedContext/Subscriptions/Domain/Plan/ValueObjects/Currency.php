<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use InvalidArgumentException;

/**
 * Currency code of a plan amount, e.g. "USDT". Free-form short upper-case code
 * (crypto and fiat alike); validates length/charset, does not enumerate values.
 */
final class Currency extends ValueObject
{
    protected function __construct(private readonly string $value)
    {
        if (preg_match('/^[A-Z0-9]{2,10}$/', $this->value) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid currency code.', $this->value));
        }
    }

    public static function from(mixed ...$values): static
    {
        return new self(mb_strtoupper(trim((string) $values[0])));
    }

    public function value(): string
    {
        return $this->value;
    }
}
