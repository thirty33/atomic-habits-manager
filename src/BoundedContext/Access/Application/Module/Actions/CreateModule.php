<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Module\Actions;

use Core\BoundedContext\Access\Application\Module\DTOs\CreateModuleData;
use Core\BoundedContext\Access\Application\Module\ModuleReader;
use Core\BoundedContext\Access\Domain\Module\Exceptions\ModuleCodeAlreadyTaken;
use Core\BoundedContext\Access\Domain\Module\Module;
use Core\BoundedContext\Access\Domain\Module\ModuleRepository;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleCode;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleName;
use Core\Shared\Application\Persistence\TransactionManager;

final readonly class CreateModule
{
    public function __construct(
        private ModuleRepository $repository,
        private ModuleReader $modules,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(CreateModuleData $data): int
    {
        $code = ModuleCode::from($data->code);

        if ($this->modules->findIdByCode($code->value()) !== null) {
            throw ModuleCodeAlreadyTaken::forCode($code);
        }

        $module = Module::create(
            code: $code,
            name: ModuleName::from($data->name),
            isActive: $data->isActive,
        );

        $this->transaction->execute(fn () => $this->repository->save($module));

        return $module->id()->value();
    }
}
