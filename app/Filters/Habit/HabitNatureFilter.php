<?php

namespace App\Filters\Habit;

use App\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

final class HabitNatureFilter extends Filter
{
    public function handle(Builder $items, \Closure $next): Builder
    {
        if (! strlen($this->filter->getValue())) {
            return $next($items);
        }

        $items->where('habit_nature', '=', $this->filter->getValue());

        return $next($items);
    }
}