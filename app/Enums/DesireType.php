<?php

namespace App\Enums;

enum DesireType: string
{
    case NEED = 'need';
    case WANT = 'want';
    case NEUTRAL = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::NEED => 'Es algo que necesito hacer',
            self::WANT => 'Es algo que quiero hacer',
            self::NEUTRAL => 'No estoy seguro aun',
        };
    }
}