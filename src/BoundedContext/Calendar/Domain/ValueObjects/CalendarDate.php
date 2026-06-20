<?php

declare(strict_types=1);

namespace Core\BoundedContext\Calendar\Domain\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use DateTimeImmutable;
use InvalidArgumentException;

final class CalendarDate extends ValueObject
{
    private DateTimeImmutable $date;

    public function __construct(DateTimeImmutable $date)
    {
        $normalized = DateTimeImmutable::createFromFormat('!Y-m-d', $date->format('Y-m-d'));
        if ($normalized === false) {
            throw new InvalidArgumentException('Could not normalize CalendarDate to Y-m-d');
        }
        $this->date = $normalized;
    }

    public static function fromString(string $date): self
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', substr(trim($date), 0, 10));
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

    public function isBefore(CalendarDate $other): bool
    {
        return $this->date < $other->date;
    }

    public function isAfter(CalendarDate $other): bool
    {
        return $this->date > $other->date;
    }
}
