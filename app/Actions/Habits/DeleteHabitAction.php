<?php

namespace App\Actions\Habits;

use App\Actions\Contracts\DeleteAction;
use App\Models\Habit;

final class DeleteHabitAction implements DeleteAction
{
    public static function execute(int $id): void
    {
        Habit::where('user_id', auth()->id())->findOrFail($id)?->delete();
    }
}