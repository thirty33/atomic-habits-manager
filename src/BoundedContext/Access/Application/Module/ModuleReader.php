<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Module;

/**
 * Read-side port for modules (CQRS counterpart of ModuleRepository).
 */
interface ModuleReader
{
    /** @return array<int, string>  id => name */
    public function options(): array;

    public function findIdByCode(string $code): ?int;
}
