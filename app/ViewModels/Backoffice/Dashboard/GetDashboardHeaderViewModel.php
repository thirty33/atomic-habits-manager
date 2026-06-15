<?php

namespace App\ViewModels\Backoffice\Dashboard;

use App\ViewModels\ViewModel;
use Carbon\Carbon;

/**
 * Header block of the dashboard (eyebrow + greeting + subtitle).
 */
class GetDashboardHeaderViewModel extends ViewModel
{
    public function eyebrow(): string
    {
        $today = Carbon::today()->locale('es');

        return ucfirst($today->isoFormat('dddd D [de] MMMM')).' · Semana '.$today->isoWeek();
    }

    public function greetingName(): string
    {
        return auth()->user()?->name ?? 'Admin';
    }

    public function subtitle(): string
    {
        return 'Un vistazo a tu día: adherencia, racha y la reflexión más reciente de tus reportes.';
    }
}
