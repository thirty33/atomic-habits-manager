<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects;

use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\InvalidBinanceEmail;
use Core\Shared\Domain\ValueObjects\ValueObject;

/**
 * The Binance account email the payer paid FROM — what the admin searches for
 * when reconciling the transfer. Validates email format and length.
 */
final class BinanceEmail extends ValueObject
{
    protected function __construct(private readonly string $value)
    {
        if ($this->value === '' || mb_strlen($this->value) > 255) {
            throw InvalidBinanceEmail::for($this->value);
        }

        if (filter_var($this->value, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidBinanceEmail::for($this->value);
        }
    }

    public static function from(mixed ...$values): static
    {
        return new self(mb_strtolower(trim((string) $values[0])));
    }

    public function value(): string
    {
        return $this->value;
    }
}
