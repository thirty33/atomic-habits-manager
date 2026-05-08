<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes;

enum MessageStatus: string
{
    case Sent = 'sent';
    case Pending = 'pending';
    case Approved = 'approved';
    case Banned = 'banned';
}
