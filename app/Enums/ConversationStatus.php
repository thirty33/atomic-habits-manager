<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Banned = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Archived => 'Archivada',
            self::Banned => 'Baneada',
        };
    }
}
