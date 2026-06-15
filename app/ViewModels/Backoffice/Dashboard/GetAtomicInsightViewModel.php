<?php

namespace App\ViewModels\Backoffice\Dashboard;

use App\ViewModels\ViewModel;
use Carbon\Carbon;
use Core\BoundedContext\DailyReports\Application\Actions\GenerateDashboardInsight;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Insights\Application\InsightReader;

/**
 * "Atomic IA · insight" card. Reads the latest LLM-generated insight persisted
 * by the daily job. Falls back to a deterministic, data-driven message while no
 * generated insight exists yet (e.g. before the first job run).
 */
class GetAtomicInsightViewModel extends ViewModel
{
    public function __construct(
        private readonly InsightReader $insights,
        private readonly GenerateDashboardInsight $fallback,
    ) {}

    public function eyebrow(): string
    {
        return 'Atomic IA · insight';
    }

    public function message(): string
    {
        $userId = UserId::from((int) auth()->id());

        return $this->insights->latestForUser($userId)?->message
            ?? ($this->fallback)($userId, Carbon::today()->toDateString());
    }
}
