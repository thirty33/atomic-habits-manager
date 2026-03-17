<?php

namespace App\Enums;

enum ReportEntryStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Partial = 'partial';
    case NotCompleted = 'not_completed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Completed => 'Completado',
            self::Partial => 'Parcial',
            self::NotCompleted => 'No cumplido',
            self::Skipped => 'Omitido',
        };
    }
}
