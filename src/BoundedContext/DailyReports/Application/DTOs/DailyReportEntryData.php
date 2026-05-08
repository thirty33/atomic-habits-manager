<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\DTOs;

final readonly class DailyReportEntryData
{
    public function __construct(
        public ?int $dailyReportEntryId,
        public ?int $habitOccurrenceId,
        public ?int $habitId,
        public ?string $customActivity,
        public string $startTime,
        public string $endTime,
        public string $status,
        public ?string $notes,
    ) {}

    /**
     * @param  array{
     *     daily_report_entry_id?: ?int,
     *     habit_occurrence_id?: ?int,
     *     habit_id?: ?int,
     *     custom_activity?: ?string,
     *     start_time: string,
     *     end_time: string,
     *     status: string,
     *     notes?: ?string,
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dailyReportEntryId: $data['daily_report_entry_id'] ?? null,
            habitOccurrenceId: $data['habit_occurrence_id'] ?? null,
            habitId: $data['habit_id'] ?? null,
            customActivity: $data['custom_activity'] ?? null,
            startTime: $data['start_time'],
            endTime: $data['end_time'],
            status: $data['status'],
            notes: $data['notes'] ?? null,
        );
    }
}
