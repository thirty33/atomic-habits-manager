<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module;

use Core\BoundedContext\Access\Domain\Module\Exceptions\ModuleNotFound;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;

/**
 * Write-side contract for modules. Bare DB operations only — the transaction
 * boundary lives in the Application use case (TransactionManager), never here.
 */
interface ModuleRepository
{
    public function save(Module $module): void;

    /** @throws ModuleNotFound */
    public function find(ModuleId $id): Module;

    public function deleteById(ModuleId $id): void;
}
