<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Role\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class RoleName extends BoundedText
{
    protected function maxLength(): int
    {
        return 200;
    }
}
