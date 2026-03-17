<?php

namespace App\Services\Frontend\UIElements\FormFields\SelectOptions;

use App\Enums\Mood;

class MoodOption implements Contracts\WithOptions
{
    public function getOptions(): array
    {
        return array_map(
            fn (Mood $case) => ['text' => $case->emoji().' '.$case->label(), 'value' => $case->value],
            Mood::cases()
        );
    }
}
