<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Calendar\Domain\ValueObjects;

use Core\BoundedContext\Calendar\Domain\ValueObjects\CalendarDate;
use Core\BoundedContext\Calendar\Domain\ValueObjects\CalendarPeriod;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalendarPeriodTest extends TestCase
{
    public function test_it_builds_a_valid_period(): void
    {
        $period = CalendarPeriod::of('2026-05-01', '2026-05-31');

        $this->assertSame('2026-05-01', $period->from->toString());
        $this->assertSame('2026-05-31', $period->to->toString());
    }

    public function test_a_single_day_period_is_valid(): void
    {
        $period = CalendarPeriod::of('2026-05-12', '2026-05-12');

        $this->assertTrue($period->contains(CalendarDate::fromString('2026-05-12')));
    }

    public function test_it_rejects_a_period_where_from_is_after_to(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CalendarPeriod::of('2026-05-31', '2026-05-01');
    }

    public function test_contains_checks_inclusive_bounds(): void
    {
        $period = CalendarPeriod::of('2026-05-10', '2026-05-16');

        $this->assertTrue($period->contains(CalendarDate::fromString('2026-05-10')));
        $this->assertTrue($period->contains(CalendarDate::fromString('2026-05-13')));
        $this->assertTrue($period->contains(CalendarDate::fromString('2026-05-16')));
        $this->assertFalse($period->contains(CalendarDate::fromString('2026-05-09')));
        $this->assertFalse($period->contains(CalendarDate::fromString('2026-05-17')));
    }
}
