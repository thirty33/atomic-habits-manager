<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Calendar\Domain\ValueObjects;

use Core\BoundedContext\Calendar\Domain\ValueObjects\CalendarDate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalendarDateTest extends TestCase
{
    public function test_it_builds_from_a_valid_y_m_d_string(): void
    {
        $date = CalendarDate::fromString('2026-05-12');

        $this->assertSame('2026-05-12', $date->toString());
        $this->assertSame('2026-05-12', $date->value());
    }

    public function test_it_truncates_a_datetime_string_to_the_date(): void
    {
        $date = CalendarDate::fromString('2026-05-12 23:40:00');

        $this->assertSame('2026-05-12', $date->toString());
    }

    public function test_it_rejects_an_invalid_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CalendarDate::fromString('not-a-date');
    }

    public function test_it_compares_dates(): void
    {
        $earlier = CalendarDate::fromString('2026-05-10');
        $later = CalendarDate::fromString('2026-05-12');

        $this->assertTrue($earlier->isBefore($later));
        $this->assertTrue($later->isAfter($earlier));
        $this->assertFalse($earlier->isAfter($later));
    }

    public function test_equal_dates_are_equal(): void
    {
        $this->assertTrue(
            CalendarDate::fromString('2026-05-12')->equals(CalendarDate::fromString('2026-05-12'))
        );
        $this->assertFalse(
            CalendarDate::fromString('2026-05-12')->equals(CalendarDate::fromString('2026-05-13'))
        );
    }
}
