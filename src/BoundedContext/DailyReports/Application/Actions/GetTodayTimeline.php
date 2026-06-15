<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\DailyReportReader;
use Core\BoundedContext\DailyReports\Application\ReadModels\TimelineRowSnapshot;
use Core\BoundedContext\HabitOccurrences\Application\HabitOccurrenceReader;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use DateTimeImmutable;

/**
 * Today's occurrences ordered by start time, each annotated with the
 * completion status from today's report (pending when not yet reported).
 */
final readonly class GetTodayTimeline
{
    public function __construct(
        private HabitOccurrenceReader $occurrences,
        private DailyReportReader $reports,
    ) {}

    /**
     * @return list<TimelineRowSnapshot>
     */
    public function __invoke(UserId $userId, ?string $today = null): array
    {
        $today ??= date('Y-m-d');

        $occurrences = $this->occurrences->findForUserOnDate($userId, OccurrenceDate::fromString($today));
        $statuses = $this->reports->entryStatusesByOccurrence($userId, $today, $today);

        $rows = [];
        foreach ($occurrences as $snapshot) {
            $rows[] = new TimelineRowSnapshot(
                time: substr($snapshot->startTime, 0, 5),
                title: $snapshot->habitName ?? '—',
                detail: $this->durationLabel(
                    $snapshot->occurrenceDate,
                    $snapshot->startTime,
                    $snapshot->endDate,
                    $snapshot->endTime,
                ),
                status: $statuses[$snapshot->habitOccurrenceId] ?? 'pending',
            );
        }

        return $rows;
    }

    private function durationLabel(string $startDate, string $startTime, string $endDate, string $endTime): string
    {
        $start = new DateTimeImmutable($startDate.' '.$startTime);
        $end = new DateTimeImmutable($endDate.' '.$endTime);
        $minutes = (int) max(0, ($end->getTimestamp() - $start->getTimestamp()) / 60);

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return sprintf('%dh %dm', $hours, $mins);
        }

        if ($hours > 0) {
            return sprintf('%dh', $hours);
        }

        return sprintf('%d min', $mins);
    }
}
