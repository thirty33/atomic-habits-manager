<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\DTOs;

final readonly class CreateHabitScheduleData
{
    public function __construct(
        public int $habitId,
        public string $startTime,
        public string $endTime,
        public string $recurrenceType,
        public ?array $daysOfWeek,
        public ?int $intervalDays,
        public ?string $specificDate,
        public ?string $startsFrom,
        public ?string $endsAt,
        public ?string $chainCue = null,
        public ?int $previousScheduleId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            habitId: (int) $data['habit_id'],
            startTime: (string) $data['start_time'],
            endTime: (string) $data['end_time'],
            recurrenceType: (string) $data['recurrence_type'],
            daysOfWeek: self::nullableArray($data, 'days_of_week'),
            intervalDays: self::nullableInt($data, 'interval_days'),
            specificDate: self::nullableString($data, 'specific_date'),
            startsFrom: self::nullableString($data, 'starts_from'),
            endsAt: self::nullableString($data, 'ends_at'),
            chainCue: self::nullableString($data, 'chain_cue'),
            previousScheduleId: self::nullableInt($data, 'previous_schedule_id'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableString(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableInt(array $data, string $key): ?int
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableArray(array $data, string $key): ?array
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null || $value === []) {
            return null;
        }

        return is_array($value) ? $value : null;
    }
}
