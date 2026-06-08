<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\HabitOccurrences\Domain;

use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrence;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceTime;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use PHPUnit\Framework\TestCase;

class HabitOccurrenceTest extends TestCase
{
    public function test_end_date_equals_anchor_for_intraday_window(): void
    {
        $occurrence = HabitOccurrence::schedule(
            HabitId::from(1),
            OccurrenceDate::fromString('2026-06-10'),
            new OccurrenceTime('09:00', '17:00'),
        );

        $this->assertSame('2026-06-10', $occurrence->endDate()->toString());
    }

    public function test_end_date_is_next_day_when_window_crosses_midnight(): void
    {
        $occurrence = HabitOccurrence::schedule(
            HabitId::from(1),
            OccurrenceDate::fromString('2026-06-10'),
            new OccurrenceTime('23:00', '07:00'),
        );

        $this->assertSame('2026-06-11', $occurrence->endDate()->toString());
    }

    public function test_end_date_rolls_over_month_boundary(): void
    {
        $occurrence = HabitOccurrence::schedule(
            HabitId::from(1),
            OccurrenceDate::fromString('2026-01-31'),
            new OccurrenceTime('23:00', '07:00'),
        );

        $this->assertSame('2026-02-01', $occurrence->endDate()->toString());
    }

    public function test_end_date_rolls_over_year_boundary(): void
    {
        $occurrence = HabitOccurrence::schedule(
            HabitId::from(1),
            OccurrenceDate::fromString('2026-12-31'),
            new OccurrenceTime('23:00', '07:00'),
        );

        $this->assertSame('2027-01-01', $occurrence->endDate()->toString());
    }

    public function test_anchor_date_is_always_the_start_day(): void
    {
        $occurrence = HabitOccurrence::schedule(
            HabitId::from(1),
            OccurrenceDate::fromString('2026-06-10'),
            new OccurrenceTime('23:00', '07:00'),
        );

        $this->assertSame('2026-06-10', $occurrence->scheduledDate()->toString());
    }
}
