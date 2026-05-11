<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Infrastructure\Persistence\Eloquent;

use App\Models\HabitSchedule as HabitScheduleModel;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;
use Core\BoundedContext\HabitSchedules\Domain\HabitSchedule;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Illuminate\Support\Facades\DB;

final readonly class EloquentHabitScheduleRepository implements HabitScheduleReader, HabitScheduleRepository
{
    public function __construct(
        private HabitScheduleModel $model,
        private DomainEventBus $bus,
    ) {}

    public function save(HabitSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $isNew = $schedule->isNew();

            if ($isNew) {
                $row = $this->model->newInstance();
                $row->fill($this->toAttributes($schedule));
                $row->save();

                $schedule->assignId(HabitScheduleId::from((int) $row->getKey()));
            } else {
                $id = $schedule->habitScheduleId();

                if ($id === null) {
                    throw new \LogicException('HabitSchedule reports !isNew() but has no id.');
                }

                $row = $this->model->newQuery()->find($id->value());

                if ($row === null) {
                    throw new \RuntimeException(sprintf(
                        'Cannot update HabitSchedule %d: row missing in DB.',
                        $id->value()
                    ));
                }

                $row->fill($this->toAttributes($schedule));
                $row->save();
            }

            $this->bus->publish(...$schedule->pullDomainEvents());
        });
    }

    public function find(HabitScheduleId $id): ?HabitSchedule
    {
        $row = $this->model->newQuery()->find($id->value());

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function delete(HabitSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $id = $schedule->habitScheduleId();

            if ($id === null) {
                throw new \LogicException('Cannot delete a HabitSchedule without id.');
            }

            $this->model->newQuery()
                ->where('habit_schedule_id', $id->value())
                ->delete();

            $this->bus->publish(...$schedule->pullDomainEvents());
        });
    }

    /**
     * @param  list<int>  $habitIds
     * @return array<int, list<HabitScheduleSnapshot>>
     */
    public function findByHabitIds(array $habitIds): array
    {
        if ($habitIds === []) {
            return [];
        }

        $rows = $this->model->newQuery()
            ->whereIn('habit_id', $habitIds)
            ->orderBy('habit_schedule_id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $attrs = $row->getAttributes();
            $habitId = (int) $attrs['habit_id'];

            $result[$habitId][] = new HabitScheduleSnapshot(
                habitScheduleId: (int) $attrs['habit_schedule_id'],
                habitId: $habitId,
                recurrenceType: (string) $attrs['recurrence_type'],
                startTime: $this->nullableString($attrs, 'start_time'),
                endTime: $this->nullableString($attrs, 'end_time'),
                daysOfWeek: $this->decodeDaysOfWeek($attrs['days_of_week'] ?? null),
                intervalDays: isset($attrs['interval_days']) ? (int) $attrs['interval_days'] : null,
                specificDate: $this->nullableString($attrs, 'specific_date'),
                startsFrom: $this->nullableString($attrs, 'starts_from'),
                endsAt: $this->nullableString($attrs, 'ends_at'),
                isActive: (bool) $attrs['is_active'],
            );
        }

        return $result;
    }

    /**
     * @param  list<int>  $habitIds
     * @return array<int, HabitScheduleSnapshot>
     */
    public function findActiveByHabitIds(array $habitIds): array
    {
        if ($habitIds === []) {
            return [];
        }

        $rows = $this->model->newQuery()
            ->whereIn('habit_id', $habitIds)
            ->where('is_active', true)
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $attrs = $row->getAttributes();
            $habitId = (int) $attrs['habit_id'];

            $result[$habitId] = new HabitScheduleSnapshot(
                habitScheduleId: (int) $attrs['habit_schedule_id'],
                habitId: $habitId,
                recurrenceType: (string) $attrs['recurrence_type'],
                startTime: $this->nullableString($attrs, 'start_time'),
                endTime: $this->nullableString($attrs, 'end_time'),
                daysOfWeek: $this->decodeDaysOfWeek($attrs['days_of_week'] ?? null),
                intervalDays: isset($attrs['interval_days']) ? (int) $attrs['interval_days'] : null,
                specificDate: $this->nullableString($attrs, 'specific_date'),
                startsFrom: $this->nullableString($attrs, 'starts_from'),
                endsAt: $this->nullableString($attrs, 'ends_at'),
                isActive: (bool) $attrs['is_active'],
            );
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(HabitSchedule $schedule): array
    {
        return [
            'habit_id' => $schedule->habitId()->value(),
            'previous_schedule_id' => $schedule->previousScheduleId()?->value(),
            'chain_cue' => $schedule->chainCue()?->value(),
            'start_time' => $schedule->timeRange()->startTime,
            'end_time' => $schedule->timeRange()->endTime,
            'recurrence_type' => $schedule->recurrenceType()->value(),
            'days_of_week' => $schedule->daysOfWeek()?->value(),
            'interval_days' => $schedule->intervalDays()?->value,
            'specific_date' => $schedule->specificDate(),
            'starts_from' => $schedule->dateRange()->startsFrom,
            'ends_at' => $schedule->dateRange()->endsAt,
            'is_active' => $schedule->isActive(),
        ];
    }

    private function toDomain(HabitScheduleModel $row): HabitSchedule
    {
        $attrs = $row->getAttributes();

        return HabitSchedule::fromPrimitives(
            habitScheduleId: (int) $attrs['habit_schedule_id'],
            habitId: (int) $attrs['habit_id'],
            previousScheduleId: isset($attrs['previous_schedule_id'])
                ? (int) $attrs['previous_schedule_id']
                : null,
            chainCue: $this->nullableString($attrs, 'chain_cue'),
            startTime: $this->trimTime((string) $attrs['start_time']),
            endTime: $this->trimTime((string) $attrs['end_time']),
            recurrenceType: (string) $attrs['recurrence_type'],
            daysOfWeek: $this->decodeDaysOfWeek($attrs['days_of_week'] ?? null),
            intervalDays: isset($attrs['interval_days']) ? (int) $attrs['interval_days'] : null,
            specificDate: $this->nullableString($attrs, 'specific_date'),
            startsFrom: (string) $attrs['starts_from'],
            endsAt: $this->nullableString($attrs, 'ends_at'),
            isActive: (bool) $attrs['is_active'],
            createdAt: $this->nullableString($attrs, 'created_at'),
            updatedAt: $this->nullableString($attrs, 'updated_at'),
        );
    }

    /**
     * '14:30:00' (raw TIME column) → '14:30'.
     */
    private function trimTime(string $time): string
    {
        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function nullableString(array $attrs, string $key): ?string
    {
        if (! array_key_exists($key, $attrs) || $attrs[$key] === null) {
            return null;
        }

        return (string) $attrs[$key];
    }

    /**
     * @return ?list<int>
     */
    private function decodeDaysOfWeek(mixed $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($decoded)) {
            return null;
        }

        return array_values(array_map(static fn ($d) => (int) $d, $decoded));
    }
}
