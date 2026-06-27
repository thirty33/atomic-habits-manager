<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use InvalidArgumentException;

/**
 * Optional transaction reference (hash/id) the user may paste so the admin can
 * locate the transfer faster. Optional at the domain boundary: build with
 * TxReference::optional($raw) which returns null for empty input.
 */
final class TxReference extends ValueObject
{
    protected function __construct(private readonly string $value)
    {
        if ($this->value === '') {
            throw new InvalidArgumentException('Transaction reference cannot be empty.');
        }

        if (mb_strlen($this->value) > 191) {
            throw new InvalidArgumentException('Transaction reference is too long.');
        }
    }

    public static function from(mixed ...$values): static
    {
        return new self(trim((string) $values[0]));
    }

    /**
     * Build the VO when a reference is present, or null when blank/absent.
     */
    public static function optional(?string $value): ?self
    {
        $value = trim((string) $value);

        return $value === '' ? null : new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
