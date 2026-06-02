<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Application\Actions;

use Core\BoundedContext\HabitSchedules\Application\DTOs\CreateHabitScheduleData;
use Core\BoundedContext\HabitSchedules\Application\DTOs\SyncHabitSchedulesData;
use Core\BoundedContext\HabitSchedules\Application\DTOs\UpdateHabitScheduleData;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * Syncs the full set of schedules of a habit in a single transaction.
 * Composes the canonical Create/Update/Delete use cases (no legacy layers).
 *
 *   - item with a habit_schedule_id belonging to the habit  → update
 *   - item without id                                       → create
 *   - current active id absent from the payload             → delete
 */
final readonly class SyncHabitSchedules
{
    public function __construct(
        private HabitScheduleReader $reader,
        private CreateHabitSchedule $create,
        private UpdateHabitSchedule $update,
        private DeleteHabitSchedule $delete,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(SyncHabitSchedulesData $data): void
    {
        $this->transaction->execute(function () use ($data): void {
            $current = $this->reader->findAllActiveByHabitIds([$data->habitId])[$data->habitId] ?? [];
            $currentIds = array_map(
                static fn (HabitScheduleSnapshot $snapshot): int => $snapshot->habitScheduleId,
                $current,
            );

            $keptIds = [];

            foreach ($data->schedules as $schedule) {
                $id = $schedule['habit_schedule_id'] ?? null;

                if ($id !== null && in_array((int) $id, $currentIds, true)) {
                    ($this->update)(UpdateHabitScheduleData::fromArray((int) $id, $schedule));
                    $keptIds[] = (int) $id;

                    continue;
                }

                ($this->create)(CreateHabitScheduleData::fromArray([...$schedule, 'habit_id' => $data->habitId]));
            }

            foreach (array_diff($currentIds, $keptIds) as $obsoleteId) {
                ($this->delete)($obsoleteId);
            }
        });
    }
}
