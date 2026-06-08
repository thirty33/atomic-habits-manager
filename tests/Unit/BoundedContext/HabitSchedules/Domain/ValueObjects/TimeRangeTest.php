<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\HabitSchedules\Domain\ValueObjects;

use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\TimeRange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TimeRangeTest extends TestCase
{
    public function test_accepts_intraday_range(): void
    {
        $range = TimeRange::from('09:00', '17:00');

        $this->assertSame('09:00', $range->startTime);
        $this->assertSame('17:00', $range->endTime);
    }

    public function test_accepts_range_that_crosses_midnight(): void
    {
        $range = TimeRange::from('23:00', '07:00');

        $this->assertSame('23:00', $range->startTime);
        $this->assertSame('07:00', $range->endTime);
    }

    public function test_rejects_equal_start_and_end(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TimeRange::from('08:00', '08:00');
    }

    public function test_rejects_out_of_range_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TimeRange::from('08:00', '08:60');
    }
}
