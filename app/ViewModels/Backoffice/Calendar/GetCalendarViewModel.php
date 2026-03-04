<?php

namespace App\ViewModels\Backoffice\Calendar;

use App\ViewModels\ViewModel;

class GetCalendarViewModel extends ViewModel
{
    public function pageTitle(): string
    {
        return __('Calendario');
    }
}
