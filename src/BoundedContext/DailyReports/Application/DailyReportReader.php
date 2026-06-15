<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application;

use Core\BoundedContext\DailyReports\Application\ReadModels\ReflectionSnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * Read-side port for DailyReport projections used by dashboard statistics.
 *
 * CQRS counterpart of DailyReportRepository (Domain, write-side). Both
 * interfaces are adapted by the same Eloquent class in Infrastructure.
 */
interface DailyReportReader
{
    /**
     * Completion status of each reported habit occurrence, for the user's
     * reports whose report_date falls within [from, to] (inclusive, Y-m-d).
     * Only entries linked to an occurrence are returned.
     *
     * @return array<int, string> habit_occurrence_id => status
     */
    public function entryStatusesByOccurrence(UserId $userId, string $from, string $to): array;

    /**
     * Distinct dates (Y-m-d) within [from, to] that have a report.
     *
     * @return list<string>
     */
    public function reportedDates(UserId $userId, string $from, string $to): array;

    /**
     * The user's most recent report reflection, or null if none exists.
     */
    public function latestReflection(UserId $userId): ?ReflectionSnapshot;
}
