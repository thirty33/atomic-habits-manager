<?php

Route::jsonGroup('dashboard', \App\Http\Controllers\Backoffice\DashboardController::class, [
    'index', 'json',
]);

Route::jsonGroup('habits', \App\Http\Controllers\Backoffice\HabitController::class, [
    'index', 'json', 'store', 'update', 'destroy',
]);

Route::jsonGroup('habit-schedules', \App\Http\Controllers\Backoffice\HabitScheduleController::class, [
    'store', 'update',
]);

Route::jsonGroup('calendar', \App\Http\Controllers\Backoffice\CalendarController::class, [
    'index', 'json',
]);

Route::get('calendar/occurrences', [\App\Http\Controllers\Backoffice\CalendarController::class, 'occurrences'])
    ->name('calendar.occurrences');

Route::jsonGroup('atomic-ia', \App\Http\Controllers\Backoffice\AtomicIAController::class, [
    'index', 'json', 'store',
]);

Route::post('atomic-ia/conversations', [\App\Http\Controllers\Backoffice\AtomicIAController::class, 'newConversation'])
    ->name('atomic-ia.new-conversation');

Route::delete('atomic-ia/conversations/{id}', [\App\Http\Controllers\Backoffice\AtomicIAController::class, 'destroyConversation'])
    ->name('atomic-ia.conversations.destroy');

Route::jsonGroup('daily-reports', \App\Http\Controllers\Backoffice\DailyReportController::class, [
    'index', 'json', 'store', 'destroy',
]);

Route::get('daily-reports/{id}/edit', [\App\Http\Controllers\Backoffice\DailyReportController::class, 'edit'])
    ->name('daily-reports.edit');

Route::get('daily-reports/{id}/edit-json', [\App\Http\Controllers\Backoffice\DailyReportController::class, 'editJson'])
    ->name('daily-reports.edit-json');

Route::put('daily-reports/{id}', [\App\Http\Controllers\Backoffice\DailyReportController::class, 'update'])
    ->name('daily-reports.update');

Route::put('daily-reports/{id}/entries', [\App\Http\Controllers\Backoffice\DailyReportController::class, 'saveEntries'])
    ->name('daily-reports.save-entries');
