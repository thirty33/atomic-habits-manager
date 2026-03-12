<?php

namespace App\Actions\Occurrences;

use App\Models\HabitOccurrence;

final class DeleteFutureOccurrencesAction
{
    /**
     * @param  array{occurrence_ids: array<int, int>}  $data
     */
    public static function execute(array $data = []): int
    {
        $ids = data_get($data, 'occurrence_ids', []);

        if (empty($ids)) {
            return 0;
        }

        return HabitOccurrence::whereIn('habit_occurrence_id', $ids)->delete();
    }
}
