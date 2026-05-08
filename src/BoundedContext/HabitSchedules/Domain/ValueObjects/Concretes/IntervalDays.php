<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes;

final readonly class IntervalDays
{
    private function __construct(public int $value) {}

    public static function from(int $value): self
    {
        if ($value < 1) {
            throw new \InvalidArgumentException(sprintf(
                'IntervalDays must be >= 1, got %d.',
                $value,
            ));
        }

        return new self($value);
    }
}
