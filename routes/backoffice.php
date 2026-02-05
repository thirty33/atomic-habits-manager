<?php

Route::jsonGroup('dashboard', \App\Http\Controllers\Backoffice\DashboardController::class, [
    'index', 'json',
]);

Route::jsonGroup('habits', \App\Http\Controllers\Backoffice\HabitController::class, [
    'index', 'json', 'store', 'update', 'destroy',
]);

Route::jsonGroup('habit-schedules', \App\Http\Controllers\Backoffice\HabitScheduleController::class, [
    'store',
]);
