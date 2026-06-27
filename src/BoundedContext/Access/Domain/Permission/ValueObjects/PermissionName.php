<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class PermissionName extends BoundedText
{
    protected function maxLength(): int
    {
        return 200;
    }
}
