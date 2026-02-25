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

Route::jsonGroup('atomic-ia', \App\Http\Controllers\Backoffice\AtomicIAController::class, [
    'index', 'json', 'store',
]);

Route::post('atomic-ia/conversations', [\App\Http\Controllers\Backoffice\AtomicIAController::class, 'newConversation'])
    ->name('atomic-ia.new-conversation');

Route::delete('atomic-ia/conversations/{id}', [\App\Http\Controllers\Backoffice\AtomicIAController::class, 'destroyConversation'])
    ->name('atomic-ia.conversations.destroy');
