<?php

declare(strict_types=1);

namespace Core\BoundedContext\Calendar\Infrastructure\Persistence\Eloquent;

use App\Models\HabitOccurrence as HabitOccurrenceModel;
use Core\BoundedContext\Calendar\Application\CalendarReader;
use Core\BoundedContext\Calendar\Application\ReadModels\CalendarBlockSnapshot;
use Core\BoundedContext\Calendar\Domain\ValueObjects\CalendarPeriod;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class EloquentCalendarReader implements CalendarReader
{
    public function __construct(private HabitOccurrenceModel $occurrences) {}

    /**
     * @return list<CalendarBlockSnapshot>
     */
    public function findBlocksForUserInRange(UserId $userId, CalendarPeriod $period): array
    {
        $rows = $this->occurrences->newQuery()
            ->select('habit_occurrences.*', 'dre.status as report_status')
            ->leftJoin('daily_report_entries as dre', 'dre.habit_occurrence_id', '=', 'habit_occurrences.habit_occurrence_id')
            ->whereHas('habit', fn (Builder $query) => $query->where('user_id', $userId->value()))
            ->whereBetween('habit_occurrences.occurrence_date', [$period->from->toString(), $period->to->toString()])
            ->with('habit:habit_id,name,color,habit_nature,desire_type,is_active')
            ->orderBy('habit_occurrences.occurrence_date')
            ->orderBy('habit_occurrences.start_time')
            ->get();

        return array_map(fn (Model $row): CalendarBlockSnapshot => $this->toSnapshot($row), $rows->all());
    }

    private function toSnapshot(Model $row): CalendarBlockSnapshot
    {
        $habit = $row->habit;

        return new CalendarBlockSnapshot(
            habitOccurrenceId: (int) $row->habit_occurrence_id,
            habitId: (int) $row->habit_id,
            habitScheduleId: $row->habit_schedule_id !== null ? (int) $row->habit_schedule_id : null,
            occurrenceDate: $row->occurrence_date->format('Y-m-d'),
            endDate: ($row->end_date ?? $row->occurrence_date)->format('Y-m-d'),
            startTime: substr((string) $row->start_time, 0, 5),
            endTime: substr((string) $row->end_time, 0, 5),
            status: $this->mapStatus(is_string($row->report_status) ? $row->report_status : null),
            habitName: $habit?->name,
            habitColor: $habit?->color,
            habitNature: $habit?->habit_nature?->value,
            desireType: $habit?->desire_type?->value,
            habitIsActive: $habit !== null ? (bool) $habit->is_active : null,
        );
    }

    private function mapStatus(?string $reportEntryStatus): string
    {
        return match ($reportEntryStatus) {
            'completed' => 'done',
            'partial' => 'partial',
            'not_completed' => 'missed',
            'skipped' => 'skipped',
            default => 'pending',
        };
    }
}
