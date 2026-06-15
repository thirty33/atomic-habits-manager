<?php

namespace App\ViewModels\Backoffice\Dashboard;

use App\ViewModels\ViewModel;
use Carbon\Carbon;
use Core\BoundedContext\DailyReports\Application\Actions\CalculateAdherenceForRange;
use Core\BoundedContext\DailyReports\Application\ReadModels\AdherenceDaySnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * "Adherencia · 7 días" bar chart card.
 */
class GetWeekAdherenceViewModel extends ViewModel
{
    private const int WARNING_THRESHOLD = 60;

    /** @var list<AdherenceDaySnapshot>|null */
    private ?array $cachedDays = null;

    public function __construct(private readonly CalculateAdherenceForRange $adherence) {}

    public function eyebrow(): string
    {
        return 'Adherencia · 7 días';
    }

    public function average(): int
    {
        $tracked = array_filter(
            $this->days7(),
            static fn (AdherenceDaySnapshot $day): bool => ! $day->isFuture && $day->total > 0,
        );

        if ($tracked === []) {
            return 0;
        }

        $sum = array_sum(array_map(static fn (AdherenceDaySnapshot $day): int => $day->percentage, $tracked));

        return (int) round($sum / count($tracked));
    }

    /**
     * @return array<int, array{label: string, active: bool}>
     */
    public function ranges(): array
    {
        return [
            ['label' => 'Semana', 'active' => true],
            ['label' => 'Mes', 'active' => false],
            ['label' => 'Trim.', 'active' => false],
        ];
    }

    /**
     * @return array<int, array{label: string, value: int, today: bool, future: bool, warning: bool}>
     */
    public function days(): array
    {
        $today = Carbon::today()->toDateString();

        return array_map(static fn (AdherenceDaySnapshot $day): array => [
            'label' => Carbon::parse($day->date)->locale('es')->isoFormat('ddd'),
            'value' => $day->percentage,
            'today' => $day->date === $today,
            'future' => $day->isFuture,
            'warning' => ! $day->isFuture && $day->total > 0 && $day->percentage < self::WARNING_THRESHOLD,
        ], $this->days7());
    }

    public function note(): string
    {
        $current = $this->average();
        $previous = $this->previousAverage();

        if ($previous === null) {
            return 'Aún no hay suficiente historial para comparar con la semana anterior.';
        }

        $diff = $current - $previous;
        if ($diff === 0) {
            return 'El promedio se mantuvo igual que la semana anterior.';
        }

        return sprintf(
            'Promedio %s %d%% respecto a la semana anterior.',
            $diff > 0 ? 'subió' : 'bajó',
            abs($diff),
        );
    }

    public function linkLabel(): string
    {
        return 'Ver informe →';
    }

    /**
     * @return list<AdherenceDaySnapshot>
     */
    private function days7(): array
    {
        return $this->cachedDays ??= ($this->adherence)(
            UserId::from((int) auth()->id()),
            Carbon::today()->subDays(6)->toDateString(),
            Carbon::today()->toDateString(),
            Carbon::today()->toDateString(),
        );
    }

    private function previousAverage(): ?int
    {
        $today = Carbon::today()->toDateString();
        $days = ($this->adherence)(
            UserId::from((int) auth()->id()),
            Carbon::today()->subDays(13)->toDateString(),
            Carbon::today()->subDays(7)->toDateString(),
            $today,
        );

        $tracked = array_filter($days, static fn (AdherenceDaySnapshot $day): bool => $day->total > 0);
        if ($tracked === []) {
            return null;
        }

        $sum = array_sum(array_map(static fn (AdherenceDaySnapshot $day): int => $day->percentage, $tracked));

        return (int) round($sum / count($tracked));
    }
}
