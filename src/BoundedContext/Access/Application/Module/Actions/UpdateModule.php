<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Module\Actions;

use Core\BoundedContext\Access\Application\Module\DTOs\UpdateModuleData;
use Core\BoundedContext\Access\Domain\Module\ModuleRepository;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleName;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * Updates a module's name and active flag. The code is catalog data fixed at
 * creation and is NOT editable here on purpose: capability codes are derived
 * from it and referenced by authorization checks.
 */
final readonly class UpdateModule
{
    public function __construct(
        private ModuleRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(UpdateModuleData $data): void
    {
        $module = $this->repository->find(ModuleId::from($data->id));
        $module->update(ModuleName::from($data->name), $data->isActive);

        $this->transaction->execute(fn () => $this->repository->save($module));
    }
}
