<?php

namespace App\Filters\DailyReport;

use App\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

final class DateRangeFilter extends Filter
{
    public function handle(Builder $items, \Closure $next): Builder
    {
        $value = $this->filter->getValue();
        $from = data_get($value, 'from');
        $to = data_get($value, 'to');

        if ($from) {
            $items->where('report_date', '>=', $from);
        }

        if ($to) {
            $items->where('report_date', '<=', $to);
        }

        return $next($items);
    }
}
