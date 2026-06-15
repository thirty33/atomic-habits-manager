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
 * Produces a short, data-driven suggestion for the dashboard insight card from
 * the user's last two weeks of reports. Deterministic (no LLM call): the
 * message changes as the underlying numbers change. The conversational agent's
 * report_analysis tool covers richer, free-form analysis.
 */
final readonly class GenerateDashboardInsight
{
    private const int WINDOW_DAYS = 14;

    public function __construct(
        private HabitOccurrenceReader $occurrences,
        private DailyReportReader $reports,
        private CalculateAdherenceForRange $adherence,
        private CalculateStreak $streak,
    ) {}

    public function __invoke(UserId $userId, ?string $today = null): string
    {
        $today ??= date('Y-m-d');
        $from = (new DateTimeImmutable($today))->modify('-'.(self::WINDOW_DAYS - 1).' days')->format('Y-m-d');

        $days = ($this->adherence)($userId, $from, $today, $today);
        $average = $this->average($days);
        $tracked = array_filter($days, static fn (AdherenceDaySnapshot $day): bool => ! $day->isFuture && $day->total > 0);

        if ($tracked === []) {
            return 'Aún no hay reportes suficientes. Registrá tus hábitos del día y en unos días verás aquí sugerencias basadas en tu progreso.';
        }

        $streak = ($this->streak)($userId, $today);
        if ($streak->count >= 3) {
            return sprintf(
                'Llevás %d días seguidos cumpliendo todo lo programado. La consistencia es interés compuesto: mantené el ritmo.',
                $streak->count,
            );
        }

        $worst = $this->worstHabit($userId, $from, $today);
        if ($worst !== null && $worst['percentage'] < 50) {
            return sprintf(
                '«%s» es el que más se te escapa (%d%% en dos semanas). Probá acortar su duración o moverlo a una hora con menos fricción.',
                $worst['name'],
                $worst['percentage'],
            );
        }

        if ($average >= 80) {
            return sprintf(
                'Tu adherencia media va en %d%%. Buen momento para subir la exigencia de un hábito o sumar uno nuevo.',
                $average,
            );
        }

        if ($worst !== null) {
            return sprintf(
                'Tu adherencia media es %d%%. Si esta semana cerrás «%s» de forma consistente, deberías verla subir.',
                $average,
                $worst['name'],
            );
        }

        return sprintf('Tu adherencia media de las últimas dos semanas es %d%%. Apuntá a cerrar el día completo para sostener la racha.', $average);
    }

    /**
     * @param  list<AdherenceDaySnapshot>  $days
     */
    private function average(array $days): int
    {
        $tracked = array_filter($days, static fn (AdherenceDaySnapshot $day): bool => ! $day->isFuture && $day->total > 0);
        if ($tracked === []) {
            return 0;
        }

        $sum = array_sum(array_map(static fn (AdherenceDaySnapshot $day): int => $day->percentage, $tracked));

        return (int) round($sum / count($tracked));
    }

    /**
     * @return array{name: string, percentage: int}|null
     */
    private function worstHabit(UserId $userId, string $from, string $to): ?array
    {
        $occurrences = $this->occurrences->findForUserInRange(
            $userId,
            OccurrenceDate::fromString($from),
            OccurrenceDate::fromString($to),
        );

        if ($occurrences === []) {
            return null;
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

        $worst = null;
        foreach ($byHabit as $name => $counts) {
            $percentage = (int) round($counts['completed'] / $counts['total'] * 100);
            if ($worst === null || $percentage < $worst['percentage']) {
                $worst = ['name' => $name, 'percentage' => $percentage];
            }
        }

        return $worst;
    }
}
