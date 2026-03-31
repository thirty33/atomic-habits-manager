<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('atomic-ia:process')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('atomic-ia:moderate')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('habits:generate-occurrences')->dailyAt('03:00')->withoutOverlapping();
