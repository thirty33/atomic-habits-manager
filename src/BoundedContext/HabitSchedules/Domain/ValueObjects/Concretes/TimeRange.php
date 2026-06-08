<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes;

final readonly class TimeRange
{
    private function __construct(
        public string $startTime,
        public string $endTime,
    ) {}

    public static function from(string $startTime, string $endTime): self
    {
        self::assertHHMM($startTime, 'startTime');
        self::assertHHMM($endTime, 'endTime');

        if ($endTime === $startTime) {
            throw new \InvalidArgumentException(sprintf(
                'endTime (%s) must differ from startTime (%s).',
                $endTime,
                $startTime,
            ));
        }

        return new self($startTime, $endTime);
    }

    private static function assertHHMM(string $value, string $field): void
    {
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                '%s must match HH:MM (00:00..23:59), got "%s".',
                $field,
                $value,
            ));
        }
    }
}
