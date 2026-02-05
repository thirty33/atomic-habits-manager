<?php

namespace App\Services\Frontend\UIElements\FormFields\SelectOptions;

use App\Enums\RecurrenceType;

class RecurrenceTypeOption implements Contracts\WithOptions
{
    public function getOptions(): array
    {
        return array_map(
            fn (RecurrenceType $case) => ['text' => __($case->label()), 'value' => $case->value],
            RecurrenceType::cases()
        );
    }
}