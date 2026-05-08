<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Primitives;

use Core\Shared\Domain\ValueObjects\ValueObject;

abstract class IntegerId extends ValueObject
{
    protected function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(sprintf(
                '%s must be a positive integer, got %d.',
                static::class,
                $value
            ));
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
