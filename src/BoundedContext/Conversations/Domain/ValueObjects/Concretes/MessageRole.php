<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
