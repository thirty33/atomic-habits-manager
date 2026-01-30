<?php

namespace App\Enums\Filters;

use App\Filters\Filter;
use App\Filters\FilterValue;
use App\Filters\Habit\DesireTypeFilter;
use App\Filters\Habit\HabitNatureFilter;
use App\Filters\Habit\QueryFilter;
use App\Filters\Habit\SorterFilter;
use App\Filters\Shared\IsActiveFilter;

enum HabitFilters: string
{
    case Sorter = 'sorter';

    case Query = 'query';

    case HabitNature = 'habit_nature';

    case DesireType = 'desire_type';

    case IsActive = 'is_active';

    public function create(FilterValue $filter): Filter
    {
        return match ($this) {
            self::Sorter => new SorterFilter(filter: $filter),
            self::Query => new QueryFilter(filter: $filter),
            self::HabitNature => new HabitNatureFilter(filter: $filter),
            self::DesireType => new DesireTypeFilter(filter: $filter),
            self::IsActive => new IsActiveFilter(filter: $filter),
        };
    }
}