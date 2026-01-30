<?php

namespace App\Enums;

enum HabitNature: string
{
    case BUILD = 'build';
    case BREAK = 'break';

    public function label(): string
    {
        return match ($this) {
            self::BUILD => 'Quiero adoptar un buen habito',
            self::BREAK => 'Quiero dejar un mal habito',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BUILD => '#22C55E',
            self::BREAK => '#EF4444',
        };
    }
}