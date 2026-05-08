<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use DateTimeImmutable;
use InvalidArgumentException;

final class ReportDate extends ValueObject
{
    private function __construct(private readonly DateTimeImmutable $date) {}

    public static function fromString(string $date): self
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($parsed === false) {
            throw new InvalidArgumentException(
                sprintf('Invalid ReportDate "%s". Expected format Y-m-d.', $date)
            );
        }

        // Normalize to midnight to avoid time-component drift.
        return new self($parsed->setTime(0, 0, 0));
    }

    public function value(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function equals(ValueObject $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        return $this->value() === $other->value();
    }
}
