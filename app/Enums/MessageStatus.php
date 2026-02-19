<?php

namespace App\Enums;

enum MessageStatus: string
{
    case Sent = 'sent';
    case Pending = 'pending';
    case Approved = 'approved';
    case Banned = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::Pending => 'Pendiente',
            self::Approved => 'Aprobado',
            self::Banned => 'Baneado',
        };
    }
}
