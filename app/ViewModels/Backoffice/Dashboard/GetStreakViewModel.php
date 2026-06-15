<?php

namespace App\ViewModels\Backoffice\Dashboard;

use App\ViewModels\ViewModel;
use Carbon\Carbon;
use Core\BoundedContext\DailyReports\Application\Actions\CalculateStreak;
use Core\BoundedContext\DailyReports\Application\ReadModels\StreakSnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * "Racha actual" card with the completion grid.
 */
class GetStreakViewModel extends ViewModel
{
    private ?StreakSnapshot $cachedStreak = null;

    public function __construct(private readonly CalculateStreak $calculateStreak) {}

    public function eyebrow(): string
    {
        return 'Racha actual';
    }

    public function count(): int
    {
        return $this->streak()->count;
    }

    public function unit(): string
    {
        return 'días';
    }

    public function record(): bool
    {
        return $this->streak()->isRecord;
    }

    /**
     * @return list<int>
     */
    public function cells(): array
    {
        return $this->streak()->cells;
    }

    public function from(): string
    {
        return Carbon::parse($this->streak()->fromDate)->locale('es')->isoFormat('D MMM');
    }

    public function to(): string
    {
        return Carbon::parse($this->streak()->toDate)->locale('es')->isoFormat('D MMM');
    }

    public function progress(): string
    {
        $cells = $this->streak()->cells;

        return sprintf('%d / %d días', array_sum($cells), count($cells));
    }

    private function streak(): StreakSnapshot
    {
        return $this->cachedStreak ??= ($this->calculateStreak)(
            UserId::from((int) auth()->id()),
            Carbon::today()->toDateString(),
        );
    }
}
