<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application;

use Core\BoundedContext\HabitOccurrences\Application\ReadModels\HabitOccurrenceSnapshot;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

/**
 * Read-side port for HabitOccurrence projections. Returns ReadModels
 * (snapshots) tailored to calendar/list UI consumers — skips aggregate
 * hydration on purpose.
 *
 * CQRS counterpart of HabitOccurrenceRepository (Domain, write-side).
 * Both interfaces are typically adapted by a single Eloquent class in
 * Infrastructure to keep the implementation DRY.
 */
interface HabitOccurrenceReader
{
    /**
     * @return list<HabitOccurrenceSnapshot>
     */
    public function findForUserInRange(
        UserId $userId,
        OccurrenceDate $from,
        OccurrenceDate $to,
    ): array;

    /**
     * @return list<HabitOccurrenceSnapshot>
     */
    public function findForUserOnDate(
        UserId $userId,
        OccurrenceDate $date,
    ): array;
}
