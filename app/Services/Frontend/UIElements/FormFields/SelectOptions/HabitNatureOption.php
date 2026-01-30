<?php

namespace App\Services\Frontend\UIElements\FormFields\SelectOptions;

use App\Enums\HabitNature;

class HabitNatureOption implements Contracts\WithOptions
{
    public function getOptions(): array
    {
        return array_map(
            fn (HabitNature $case) => ['text' => __($case->label()), 'value' => $case->value],
            HabitNature::cases()
        );
    }
}