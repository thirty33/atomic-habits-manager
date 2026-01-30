<?php

namespace App\Filters\Habit;

use App\Filters\BaseSorterFilter;

final class SorterFilter extends BaseSorterFilter
{
    public string $primaryKey = 'habit_id';

    public array $availableColumnSorters = [
        'name', 'habit_nature', 'desire_type', 'is_active', 'created_at',
    ];
}