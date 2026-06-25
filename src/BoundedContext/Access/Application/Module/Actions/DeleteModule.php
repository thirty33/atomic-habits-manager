<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Module\Actions;

use Core\BoundedContext\Access\Domain\Module\ModuleRepository;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\Shared\Application\Persistence\TransactionManager;

final readonly class DeleteModule
{
    public function __construct(
        private ModuleRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(int $moduleId): void
    {
        $this->transaction->execute(function () use ($moduleId): void {
            $this->repository->deleteById(ModuleId::from($moduleId));
        });
    }
}
