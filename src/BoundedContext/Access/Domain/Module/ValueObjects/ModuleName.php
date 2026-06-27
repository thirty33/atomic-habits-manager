<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class ModuleName extends BoundedText
{
    protected function maxLength(): int
    {
        return 200;
    }
}
