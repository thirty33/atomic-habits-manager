<?php

namespace App\ViewModels\Backoffice;

use App\ViewModels\Backoffice\Dashboard\GetAtomicInsightViewModel;
use App\ViewModels\Backoffice\Dashboard\GetDashboardHeaderViewModel;
use App\ViewModels\Backoffice\Dashboard\GetDashboardKpisViewModel;
use App\ViewModels\Backoffice\Dashboard\GetRecentReflectionViewModel;
use App\ViewModels\Backoffice\Dashboard\GetStreakViewModel;
use App\ViewModels\Backoffice\Dashboard\GetTodayTimelineViewModel;
use App\ViewModels\Backoffice\Dashboard\GetWeekAdherenceViewModel;
use App\ViewModels\ViewModel;

/**
 * Main dashboard ViewModel. It only composes one ViewModel per view section so
 * each section owns its own (future) statistics logic; this class wires them
 * together into the JSON payload consumed by the dashboard page.
 */
class GetDashboardViewModel extends ViewModel
{
    public function __construct(
        protected readonly GetDashboardHeaderViewModel $headerViewModel,
        protected readonly GetDashboardKpisViewModel $kpisViewModel,
        protected readonly GetTodayTimelineViewModel $todayTimelineViewModel,
        protected readonly GetWeekAdherenceViewModel $weekAdherenceViewModel,
        protected readonly GetStreakViewModel $streakViewModel,
        protected readonly GetAtomicInsightViewModel $atomicInsightViewModel,
        protected readonly GetRecentReflectionViewModel $recentReflectionViewModel,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function header(): array
    {
        return $this->headerViewModel->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function kpis(): array
    {
        return $this->kpisViewModel->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function todayTimeline(): array
    {
        return $this->todayTimelineViewModel->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function weekAdherence(): array
    {
        return $this->weekAdherenceViewModel->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function streak(): array
    {
        return $this->streakViewModel->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function atomicInsight(): array
    {
        return $this->atomicInsightViewModel->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function recentReflection(): array
    {
        return $this->recentReflectionViewModel->toArray();
    }
}
