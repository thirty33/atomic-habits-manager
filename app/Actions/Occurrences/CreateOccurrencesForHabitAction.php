<?php

namespace App\Actions\Occurrences;

use App\Models\HabitOccurrence;

final class CreateOccurrencesForHabitAction
{
    /**
     * @param  array{occurrences: array<int, array<string, mixed>>}  $data
     */
    public static function execute(array $data = []): int
    {
        $occurrences = data_get($data, 'occurrences', []);

        if (empty($occurrences)) {
            return 0;
        }

        HabitOccurrence::insert($occurrences);

        return count($occurrences);
    }
}
