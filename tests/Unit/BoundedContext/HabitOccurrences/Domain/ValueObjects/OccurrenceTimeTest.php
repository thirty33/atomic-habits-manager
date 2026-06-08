<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\HabitOccurrences\Domain\ValueObjects;

use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OccurrenceTimeTest extends TestCase
{
    public function test_accepts_intraday_window(): void
    {
        $time = new OccurrenceTime('09:00', '17:00');

        $this->assertSame('09:00', $time->startTime());
        $this->assertSame('17:00', $time->endTime());
    }

    public function test_accepts_window_that_crosses_midnight(): void
    {
        $time = new OccurrenceTime('23:00', '07:00');

        $this->assertSame('23:00', $time->startTime());
        $this->assertSame('07:00', $time->endTime());
    }

    public function test_accepts_window_ending_exactly_at_midnight(): void
    {
        $time = new OccurrenceTime('23:00', '00:00');

        $this->assertSame('23:00', $time->startTime());
        $this->assertSame('00:00', $time->endTime());
    }

    public function test_rejects_equal_start_and_end(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OccurrenceTime('08:00', '08:00');
    }

    public function test_duration_for_intraday_window(): void
    {
        $this->assertSame(480, (new OccurrenceTime('09:00', '17:00'))->durationMinutes());
    }

    public function test_duration_across_midnight(): void
    {
        $this->assertSame(480, (new OccurrenceTime('23:00', '07:00'))->durationMinutes());
    }

    public function test_duration_for_one_hour_crossing_midnight(): void
    {
        $this->assertSame(60, (new OccurrenceTime('23:00', '00:00'))->durationMinutes());
    }
}
