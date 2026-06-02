<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class ImplementationIntention extends BoundedText
{
    protected function maxLength(): int
    {
        return 500;
    }
}
