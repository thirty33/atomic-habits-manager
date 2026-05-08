<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\Exceptions;

use DomainException;

final class MessageNotFound extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Message {$id} not found.");
    }
}
