<?php

namespace App\Enums\Filters;

use App\Filters\DailyReport\DateRangeFilter;
use App\Filters\DailyReport\MoodFilter;
use App\Filters\DailyReport\SorterFilter;
use App\Filters\Filter;
use App\Filters\FilterValue;

enum DailyReportFilters: string
{
    case Sorter = 'sorter';

    case Mood = 'mood';

    case DateRange = 'date_range';

    public function create(FilterValue $filter): Filter
    {
        return match ($this) {
            self::Sorter => new SorterFilter(filter: $filter),
            self::Mood => new MoodFilter(filter: $filter),
            self::DateRange => new DateRangeFilter(filter: $filter),
        };
    }
}
