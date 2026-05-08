<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use DateTimeImmutable;
use InvalidArgumentException;

final class OccurrenceDate extends ValueObject
{
    private DateTimeImmutable $date;

    public function __construct(DateTimeImmutable $date)
    {
        $normalized = DateTimeImmutable::createFromFormat('!Y-m-d', $date->format('Y-m-d'));
        if ($normalized === false) {
            throw new InvalidArgumentException('Could not normalize OccurrenceDate to Y-m-d');
        }
        $this->date = $normalized;
    }

    public static function fromString(string $date): self
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsed === false) {
            throw new InvalidArgumentException('Invalid date format. Expected Y-m-d');
        }

        return new self($parsed);
    }

    public function toString(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function value(): string
    {
        return $this->toString();
    }

    public function equals(ValueObject $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        return $this->value() === $other->value();
    }

    public function isBefore(OccurrenceDate $other): bool
    {
        return $this->date < $other->date;
    }

    public function isAfter(OccurrenceDate $other): bool
    {
        return $this->date > $other->date;
    }

    public function isPast(?DateTimeImmutable $reference = null): bool
    {
        $today = ($reference ?? new DateTimeImmutable('today'))->setTime(0, 0, 0);

        return $this->date < $today;
    }

    public function isToday(?DateTimeImmutable $reference = null): bool
    {
        $today = ($reference ?? new DateTimeImmutable('today'))->format('Y-m-d');

        return $this->toString() === $today;
    }
}
