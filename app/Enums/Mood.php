<?php

namespace App\Enums;

enum Mood: string
{
    case Great = 'great';
    case Good = 'good';
    case Neutral = 'neutral';
    case Bad = 'bad';
    case Terrible = 'terrible';

    public function label(): string
    {
        return match ($this) {
            self::Great => 'Genial',
            self::Good => 'Bien',
            self::Neutral => 'Normal',
            self::Bad => 'Mal',
            self::Terrible => 'Terrible',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Great => '😄',
            self::Good => '🙂',
            self::Neutral => '😐',
            self::Bad => '😕',
            self::Terrible => '😞',
        };
    }
}
