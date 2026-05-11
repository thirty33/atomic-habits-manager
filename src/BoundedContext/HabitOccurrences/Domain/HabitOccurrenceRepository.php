<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain;

use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;

/**
 * Write-side port for the HabitOccurrence aggregate. CQRS: read-side
 * queries that return projections live in HabitOccurrenceReader (Application).
 *
 * Purity rules: zero imports from Application, Illuminate, or App\.
 */
interface HabitOccurrenceRepository
{
    public function find(HabitOccurrenceId $id): ?HabitOccurrence;

    /**
     * @param  list<HabitOccurrence>  $occurrences
     * @return int Number of rows inserted.
     */
    public function saveMany(array $occurrences): int;

    /**
     * @param  list<HabitOccurrenceId>  $ids
     * @return int Number of rows deleted.
     */
    public function deleteByIds(array $ids): int;

    /**
     * @return list<HabitOccurrenceId>
     */
    public function futureIdsForHabit(HabitId $habitId, OccurrenceDate $today): array;

    public function lastDateForHabit(HabitId $habitId): ?OccurrenceDate;
}
