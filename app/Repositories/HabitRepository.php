<?php

namespace App\Repositories;

use App\Models\Habit;
use Illuminate\Support\Collection;

class HabitRepository
{
    public function getAllForUser(int $userId): Collection
    {
        return Habit::query()
            ->forUser($userId)
            ->with('schedules')
            ->latest()
            ->get();
    }
}
