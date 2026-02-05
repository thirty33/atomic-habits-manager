<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case NONE = 'none';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case EVERY_N_DAYS = 'every_n_days';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Solo una vez',
            self::DAILY => 'Todos los días',
            self::WEEKLY => 'Algunos días de la semana',
            self::EVERY_N_DAYS => 'Cada ciertos días',
        };
    }
}