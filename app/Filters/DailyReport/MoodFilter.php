<?php

namespace App\Filters\DailyReport;

use App\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

final class MoodFilter extends Filter
{
    public function handle(Builder $items, \Closure $next): Builder
    {
        if (! strlen($this->filter->getValue())) {
            return $next($items);
        }

        $items->where('mood', $this->filter->getValue());

        return $next($items);
    }
}
