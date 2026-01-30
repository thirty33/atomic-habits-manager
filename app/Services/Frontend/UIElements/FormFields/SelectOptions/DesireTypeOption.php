<?php

namespace App\Services\Frontend\UIElements\FormFields\SelectOptions;

use App\Enums\DesireType;

class DesireTypeOption implements Contracts\WithOptions
{
    public function getOptions(): array
    {
        return array_map(
            fn (DesireType $case) => ['text' => __($case->label()), 'value' => $case->value],
            DesireType::cases()
        );
    }
}