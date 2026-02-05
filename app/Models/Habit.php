<?php

namespace App\Models;

use App\Models\Builders\HabitBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Base\Habit
{
    public function newEloquentBuilder($query): HabitBuilder
    {
        return new HabitBuilder($query);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(HabitSchedule::class, 'habit_id', 'habit_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(HabitOccurrence::class, 'habit_id', 'habit_id');
    }
}