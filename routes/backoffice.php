<?php

Route::jsonGroup('dashboard', \App\Http\Controllers\Backoffice\DashboardController::class, [
    'index', 'json',
]);


Route::jsonGroup('habits', \App\Http\Controllers\Backoffice\HabitController::class, [
    'index', 'json', 'store', 'update', 'destroy',
]);
