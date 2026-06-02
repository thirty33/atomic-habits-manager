<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Infrastructure\Persistence\Eloquent;

use App\Models\HabitOccurrence as HabitOccurrenceModel;
use Core\BoundedContext\HabitOccurrences\Application\HabitOccurrenceReader;
use Core\BoundedContext\HabitOccurrences\Application\ReadModels\HabitOccurrenceSnapshot;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrence;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrenceRepository;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceTime;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

final readonly class EloquentHabitOccurrenceRepository implements HabitOccurrenceReader, HabitOccurrenceRepository
{
    public function __construct(private HabitOccurrenceModel $model) {}

    public function find(HabitOccurrenceId $id): ?HabitOccurrence
    {
        $row = $this->model->newQuery()
            ->where('habit_occurrence_id', $id->value())
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    /**
     * @param  list<HabitOccurrence>  $occurrences
     */
    public function saveMany(array $occurrences): int
    {
        if ($occurrences === []) {
            return 0;
        }

        $rows = array_map(fn (HabitOccurrence $o) => $this->toRow($o), $occurrences);

        $this->model->newQuery()->insert($rows);

        return count($rows);
    }

    /**
     * @param  list<HabitOccurrenceId>  $ids
     */
    public function deleteByIds(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $primitive = array_map(static fn (HabitOccurrenceId $id) => $id->value(), $ids);

        return $this->model->newQuery()
            ->whereIn('habit_occurrence_id', $primitive)
            ->delete();
    }

    /**
     * @return list<HabitOccurrenceId>
     */
    public function futureIdsForHabit(HabitId $habitId, OccurrenceDate $today): array
    {
        $rows = $this->model->newQuery()
            ->where('habit_id', $habitId->value())
            ->where('occurrence_date', '>=', $today->toString())
            ->pluck('habit_occurrence_id')
            ->all();

        return array_values(array_map(
            static fn ($raw) => HabitOccurrenceId::from((int) $raw),
            $rows,
        ));
    }

    public function lastDateForHabit(HabitId $habitId): ?OccurrenceDate
    {
        $raw = $this->model->newQuery()
            ->where('habit_id', $habitId->value())
            ->max('occurrence_date');

        return $raw !== null ? OccurrenceDate::fromString((string) $raw) : null;
    }

    /**
     * @return list<HabitOccurrenceSnapshot>
     */
    public function findForUserInRange(UserId $userId, OccurrenceDate $from, OccurrenceDate $to): array
    {
        $rows = $this->model->newQuery()
            ->whereHas('habit', fn ($q) => $q->where('user_id', $userId->value()))
            ->whereBetween('occurrence_date', [$from->toString(), $to->toString()])
            ->with('habit:habit_id,name,color,habit_nature,desire_type,is_active')
            ->orderBy('occurrence_date')
            ->orderBy('start_time')
            ->get();

        return array_values($rows->map(fn ($row) => $this->toSnapshot($row))->all());
    }

    /**
     * @return list<HabitOccurrenceSnapshot>
     */
    public function findForUserOnDate(UserId $userId, OccurrenceDate $date): array
    {
        $rows = $this->model->newQuery()
            ->whereHas('habit', fn ($q) => $q->where('user_id', $userId->value()))
            ->where('occurrence_date', $date->toString())
            ->with('habit:habit_id,name,color,habit_nature,desire_type,is_active')
            ->orderBy('start_time')
            ->get();

        return array_values($rows->map(fn ($row) => $this->toSnapshot($row))->all());
    }

    private function toDomain(HabitOccurrenceModel $row): HabitOccurrence
    {
        $attrs = $row->getAttributes();

        $occurrence = HabitOccurrence::reconstitute(
            id: HabitOccurrenceId::from((int) $attrs['habit_occurrence_id']),
            habitId: HabitId::from((int) $attrs['habit_id']),
            scheduledDate: OccurrenceDate::fromString((string) $attrs['occurrence_date']),
            timeWindow: new OccurrenceTime(
                (string) $attrs['start_time'],
                (string) $attrs['end_time'],
            ),
            scheduleId: isset($attrs['habit_schedule_id']) && $attrs['habit_schedule_id'] !== null
                ? HabitScheduleId::from((int) $attrs['habit_schedule_id'])
                : null,
            createdAt: $this->parseTimestamp($attrs['created_at'] ?? null) ?? new DateTimeImmutable,
            updatedAt: $this->parseTimestamp($attrs['updated_at'] ?? null),
        );

        return $occurrence;
    }

    private function toRow(HabitOccurrence $occurrence): array
    {
        $now = Carbon::now();

        return [
            'habit_id' => $occurrence->habitId()->value(),
            'habit_schedule_id' => $occurrence->scheduleId()?->value(),
            'occurrence_date' => $occurrence->scheduledDate()->toString(),
            'start_time' => $occurrence->timeWindow()->startTime(),
            'end_time' => $occurrence->timeWindow()->endTime(),
            'created_at' => $occurrence->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $occurrence->updatedAt()?->format('Y-m-d H:i:s') ?? $now->format('Y-m-d H:i:s'),
        ];
    }

    private function toSnapshot(HabitOccurrenceModel $row): HabitOccurrenceSnapshot
    {
        $attrs = $row->getAttributes();
        $habit = $row->habit;

        return new HabitOccurrenceSnapshot(
            habitOccurrenceId: (int) $attrs['habit_occurrence_id'],
            habitId: (int) $attrs['habit_id'],
            habitScheduleId: isset($attrs['habit_schedule_id']) && $attrs['habit_schedule_id'] !== null
                ? (int) $attrs['habit_schedule_id']
                : null,
            occurrenceDate: (string) $attrs['occurrence_date'],
            startTime: substr((string) $attrs['start_time'], 0, 5),
            endTime: substr((string) $attrs['end_time'], 0, 5),
            habitName: $habit?->name,
            habitColor: $habit?->color,
            habitNature: $habit?->habit_nature?->value,
            desireType: $habit?->desire_type?->value,
            habitIsActive: $habit?->is_active === null ? null : (bool) $habit->is_active,
        );
    }

    private function parseTimestamp(mixed $raw): ?DateTimeImmutable
    {
        if ($raw === null) {
            return null;
        }
        if ($raw instanceof DateTimeImmutable) {
            return $raw;
        }
        if ($raw instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($raw);
        }

        return new DateTimeImmutable((string) $raw);
    }
}
