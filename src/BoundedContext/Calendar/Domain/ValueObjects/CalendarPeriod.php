<?php

declare(strict_types=1);

namespace Core\BoundedContext\Calendar\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CalendarPeriod
{
    public function __construct(
        public CalendarDate $from,
        public CalendarDate $to,
    ) {
        if ($from->isAfter($to)) {
            throw new InvalidArgumentException('CalendarPeriod: from cannot be after to');
        }
    }

    public static function of(string $from, string $to): self
    {
        return new self(CalendarDate::fromString($from), CalendarDate::fromString($to));
    }

    public function contains(CalendarDate $date): bool
    {
        return ! $date->isBefore($this->from) && ! $date->isAfter($this->to);
    }
}
