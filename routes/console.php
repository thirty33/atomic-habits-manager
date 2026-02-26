<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:work --stop-when-empty')->everySecond()->withoutOverlapping(expiresAt: 1)->runInBackground();
Schedule::command('atomic-ia:process')->everySecond()->withoutOverlapping(expiresAt: 1)->runInBackground();
Schedule::command('atomic-ia:moderate')->everySecond()->withoutOverlapping(expiresAt: 1)->runInBackground();
