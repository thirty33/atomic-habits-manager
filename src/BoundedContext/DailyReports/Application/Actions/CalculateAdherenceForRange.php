<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\DailyReportReader;
use Core\BoundedContext\DailyReports\Application\ReadModels\AdherenceDaySnapshot;
use Core\BoundedContext\HabitOccurrences\Application\HabitOccurrenceReader;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use DateTimeImmutable;

/**
 * Per-day adherence over a date range: for each day, how many of the user's
 * scheduled occurrences were marked completed in their daily report.
 */
final readonly class CalculateAdherenceForRange
{
    public function __construct(
        private HabitOccurrenceReader $occurrences,
        private DailyReportReader $reports,
    ) {}

    /**
     * @return list<AdherenceDaySnapshot> ordered from $from to $to inclusive
     */
    public function __invoke(UserId $userId, string $from, string $to, ?string $today = null): array
    {
        $today ??= date('Y-m-d');

        $occurrences = $this->occurrences->findForUserInRange(
            $userId,
            OccurrenceDate::fromString($from),
            OccurrenceDate::fromString($to),
        );

        $occurrenceIdsByDate = [];
        foreach ($occurrences as $snapshot) {
            $occurrenceIdsByDate[$snapshot->occurrenceDate][] = $snapshot->habitOccurrenceId;
        }

        $statuses = $this->reports->entryStatusesByOccurrence($userId, $from, $to);

        $days = [];
        foreach ($this->datesInRange($from, $to) as $date) {
            $ids = $occurrenceIdsByDate[$date] ?? [];
            $total = count($ids);
            $completed = 0;
            foreach ($ids as $occurrenceId) {
                if (($statuses[$occurrenceId] ?? null) === 'completed') {
                    $completed++;
                }
            }

            $days[] = new AdherenceDaySnapshot(
                date: $date,
                total: $total,
                completed: $completed,
                percentage: $total > 0 ? (int) round($completed / $total * 100) : 0,
                isFuture: $date > $today,
            );
        }

        return $days;
    }

    /**
     * @return list<string>
     */
    private function datesInRange(string $from, string $to): array
    {
        $dates = [];
        $cursor = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);

        while ($cursor <= $end) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        return $dates;
    }
}
