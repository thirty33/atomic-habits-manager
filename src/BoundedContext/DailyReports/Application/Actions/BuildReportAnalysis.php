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
 * Builds the plain-text "facts" block describing the user's last two weeks of
 * reports: headline adherence, streak, reporting rate, per-habit completion and
 * the latest reflection. Shared by the conversational report_analysis tool and
 * the dashboard insight generator so both read from one source of truth. The
 * facts carry no instruction — each consumer frames them for its own purpose.
 */
final readonly class BuildReportAnalysis
{
    private const int WINDOW_DAYS = 14;

    public function __construct(
        private HabitOccurrenceReader $occurrences,
        private DailyReportReader $reports,
        private CalculateAdherenceForRange $adherence,
        private CalculateStreak $streak,
        private CalculateReportingRate $reporting,
    ) {}

    public function __invoke(UserId $userId, ?string $today = null): string
    {
        $today ??= date('Y-m-d');
        $from = (new DateTimeImmutable($today))->modify('-'.(self::WINDOW_DAYS - 1).' days')->format('Y-m-d');

        $days = ($this->adherence)($userId, $from, $today, $today);
        $streak = ($this->streak)($userId, $today);
        $reporting = ($this->reporting)($userId, 30, $today);

        $lines = [];
        $lines[] = 'Análisis de los reportes del usuario (últimos '.self::WINDOW_DAYS.' días):';
        $lines[] = '- Adherencia media: '.$this->averageAdherence($days).'%';
        $lines[] = '- Racha actual: '.$streak->count.' día(s)'.($streak->isRecord ? ' (récord personal)' : '');
        $lines[] = '- Tasa de reporte (30 días): '.$reporting->percentage.'% ('.$reporting->reportedDays.'/'.$reporting->windowDays.' días)';
        $lines[] = '';
        $lines[] = 'Cumplimiento por hábito (completados/total · %):';

        foreach ($this->completionByHabit($userId, $from, $today) as $line) {
            $lines[] = '  - '.$line;
        }

        $lines[] = '';
        $lines[] = $this->latestReflectionLine($userId);

        return implode("\n", $lines);
    }

    /**
     * @param  list<AdherenceDaySnapshot>  $days
     */
    private function averageAdherence(array $days): int
    {
        $tracked = array_filter($days, static fn (AdherenceDaySnapshot $day): bool => ! $day->isFuture && $day->total > 0);
        if ($tracked === []) {
            return 0;
        }

        $sum = array_sum(array_map(static fn (AdherenceDaySnapshot $day): int => $day->percentage, $tracked));

        return (int) round($sum / count($tracked));
    }

    /**
     * @return list<string>
     */
    private function completionByHabit(UserId $userId, string $from, string $to): array
    {
        $occurrences = $this->occurrences->findForUserInRange(
            $userId,
            OccurrenceDate::fromString($from),
            OccurrenceDate::fromString($to),
        );

        if ($occurrences === []) {
            return ['(sin ocurrencias programadas en el periodo)'];
        }

        $statuses = $this->reports->entryStatusesByOccurrence($userId, $from, $to);

        $byHabit = [];
        foreach ($occurrences as $occurrence) {
            $name = $occurrence->habitName ?? ('Hábito #'.$occurrence->habitId);
            $byHabit[$name] ??= ['total' => 0, 'completed' => 0];
            $byHabit[$name]['total']++;
            if (($statuses[$occurrence->habitOccurrenceId] ?? null) === 'completed') {
                $byHabit[$name]['completed']++;
            }
        }

        $lines = [];
        foreach ($byHabit as $name => $counts) {
            $percentage = $counts['total'] > 0 ? (int) round($counts['completed'] / $counts['total'] * 100) : 0;
            $lines[] = sprintf('%s: %d/%d (%d%%)', $name, $counts['completed'], $counts['total'], $percentage);
        }

        return $lines;
    }

    private function latestReflectionLine(UserId $userId): string
    {
        $reflection = $this->reports->latestReflection($userId);

        if ($reflection === null || $reflection->notes === null || $reflection->notes === '') {
            return 'El usuario aún no ha escrito reflexiones en sus reportes.';
        }

        $mood = $reflection->mood !== null ? ' (ánimo: '.$reflection->mood.')' : '';

        return 'Última reflexión ('.$reflection->reportDate.$mood.'): "'.$reflection->notes.'"';
    }
}
