<?php

namespace App\ViewModels\Backoffice;

use App\Models\Habit;
use App\Models\User;
use App\Services\Frontend\UIElements\StatItems\StatDefault;
use App\Services\Frontend\StatsGenerator;
use App\Traits\ViewModels\WithUser;
use App\ViewModels\ViewModel;

class GetDashboardViewModel extends ViewModel
{
    use WithUser;

    public function __construct(
        protected readonly StatsGenerator $statsGenerator,
    ) {}

    public function stats(): array
    {
        return $this->statsGenerator
            ->addStat(
                new StatDefault(
                    label: 'Total usuarios',
                    value: User::count(),
                )
            )->addStat(
                new StatDefault(
                    label: 'Total hábitos',
                    value: Habit::count(),
                )
            )->getStats();
    }
}
