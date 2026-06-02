<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class PersonName extends BoundedText
{
    protected function maxLength(): int
    {
        return 255;
    }
}
