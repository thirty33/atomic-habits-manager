<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain\Services;

use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use InvalidArgumentException;

final readonly class DateRange
{
    public function __construct(
        public OccurrenceDate $from,
        public OccurrenceDate $to,
    ) {
        if ($from->isAfter($to)) {
            throw new InvalidArgumentException('DateRange: from cannot be after to');
        }
    }

    public function contains(OccurrenceDate $date): bool
    {
        return ! $date->isBefore($this->from) && ! $date->isAfter($this->to);
    }

    public function isEmpty(): bool
    {
        return false;
    }
}
