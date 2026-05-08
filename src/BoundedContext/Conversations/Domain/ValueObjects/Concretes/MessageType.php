<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
}
