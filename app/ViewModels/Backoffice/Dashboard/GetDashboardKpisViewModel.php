<?php

namespace App\ViewModels\Backoffice\Dashboard;

use App\ViewModels\ViewModel;
use Carbon\Carbon;
use Core\BoundedContext\DailyReports\Application\Actions\CalculateAdherenceForRange;
use Core\BoundedContext\DailyReports\Application\Actions\CalculateReportingRate;
use Core\BoundedContext\DailyReports\Application\Actions\CalculateStreak;
use Core\BoundedContext\Habits\Application\Actions\CountActiveHabits;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * The four KPI cards: today's adherence, active habits, streak and reporting rate.
 */
class GetDashboardKpisViewModel extends ViewModel
{
    public function __construct(
        private readonly CalculateAdherenceForRange $adherence,
        private readonly CountActiveHabits $countActiveHabits,
        private readonly CalculateStreak $calculateStreak,
        private readonly CalculateReportingRate $reportingRate,
    ) {}

    /**
     * @return array<int, array{label: string, value: string, delta: ?string, sublabel: string}>
     */
    public function items(): array
    {
        $userId = UserId::from((int) auth()->id());
        $today = Carbon::today()->toDateString();

        $series = ($this->adherence)($userId, Carbon::today()->subDays(1)->toDateString(), $today, $today);
        $todayDay = $series[count($series) - 1] ?? null;
        $yesterdayDay = $series[count($series) - 2] ?? null;

        $todayPercentage = $todayDay?->percentage ?? 0;
        $adherenceDelta = ($todayDay && $yesterdayDay && $todayDay->total > 0 && $yesterdayDay->total > 0)
            ? $this->delta($todayPercentage - $yesterdayDay->percentage)
            : null;

        $activeHabits = ($this->countActiveHabits)($userId);
        $streak = ($this->calculateStreak)($userId, $today);

        $reporting = ($this->reportingRate)($userId, 30, $today);
        $previousReporting = ($this->reportingRate)($userId, 30, Carbon::today()->subDays(30)->toDateString());

        return [
            [
                'label' => 'Adherencia hoy',
                'value' => $todayPercentage.'%',
                'delta' => $adherenceDelta,
                'sublabel' => $todayDay && $todayDay->total > 0
                    ? sprintf('%d de %d bloques cerrados', $todayDay->completed, $todayDay->total)
                    : 'Sin bloques programados hoy',
            ],
            [
                'label' => 'Hábitos activos',
                'value' => (string) $activeHabits,
                'delta' => null,
                'sublabel' => $activeHabits === 1 ? '1 en seguimiento' : $activeHabits.' en seguimiento',
            ],
            [
                'label' => 'Racha',
                'value' => (string) $streak->count,
                'delta' => null,
                'sublabel' => $streak->isRecord ? 'días sin romper · récord personal' : 'días sin romper',
            ],
            [
                'label' => 'Reportes',
                'value' => $reporting->percentage.'%',
                'delta' => $this->delta($reporting->percentage - $previousReporting->percentage),
                'sublabel' => 'completados últimos 30 días',
            ],
        ];
    }

    private function delta(int $difference): ?string
    {
        if ($difference === 0) {
            return null;
        }

        return ($difference > 0 ? '+' : '-').abs($difference);
    }
}
