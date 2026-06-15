<?php

namespace App\ViewModels\Backoffice\Dashboard;

use App\ViewModels\ViewModel;
use Carbon\Carbon;
use Core\BoundedContext\DailyReports\Application\Actions\GetTodayTimeline;
use Core\BoundedContext\DailyReports\Application\ReadModels\TimelineRowSnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * "Timeline del día" card: today's occurrences with their completion status.
 */
class GetTodayTimelineViewModel extends ViewModel
{
    /** @var list<TimelineRowSnapshot>|null */
    private ?array $cachedRows = null;

    public function __construct(private readonly GetTodayTimeline $timeline) {}

    public function eyebrow(): string
    {
        return 'Hoy · '.Carbon::today()->locale('es')->isoFormat('dddd D MMM');
    }

    public function title(): string
    {
        return 'Timeline del día';
    }

    /**
     * @return array<int, array{label: string, tone: string}>
     */
    public function summary(): array
    {
        $counts = ['completed' => 0, 'partial' => 0, 'pending' => 0, 'not_completed' => 0, 'skipped' => 0];
        foreach ($this->rowSnapshots() as $row) {
            $counts[$row->status] = ($counts[$row->status] ?? 0) + 1;
        }

        $summary = [];
        if ($counts['completed'] > 0) {
            $summary[] = ['label' => $counts['completed'].' completados', 'tone' => 'success'];
        }
        if ($counts['partial'] > 0) {
            $summary[] = ['label' => $counts['partial'].' parcial', 'tone' => 'warning'];
        }
        if ($counts['pending'] > 0) {
            $summary[] = ['label' => $counts['pending'].' pendientes', 'tone' => 'neutral'];
        }

        return $summary;
    }

    /**
     * @return array<int, array{time: string, title: string, detail: string, status: string}>
     */
    public function rows(): array
    {
        return array_map(static fn (TimelineRowSnapshot $row): array => [
            'time' => $row->time,
            'title' => $row->title,
            'detail' => $row->detail,
            'status' => $row->status,
        ], $this->rowSnapshots());
    }

    /**
     * @return list<TimelineRowSnapshot>
     */
    private function rowSnapshots(): array
    {
        return $this->cachedRows ??= ($this->timeline)(
            UserId::from((int) auth()->id()),
            Carbon::today()->toDateString(),
        );
    }
}
