<?php

namespace App\Models;

use App\Models\Builders\HabitBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}