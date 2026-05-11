<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Persistence\Eloquent;

use Core\Shared\Application\Persistence\TransactionManager;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Eloquent adapter for the TransactionManager port. Wraps the callable
 * in DB::transaction() through the framework's connection resolver, so
 * Application code stays free of Illuminate facades.
 */
final readonly class EloquentTransactionManager implements TransactionManager
{
    public function __construct(
        private ConnectionResolverInterface $connections,
    ) {}

    public function execute(callable $work): mixed
    {
        return $this->connections->connection()->transaction($work);
    }
}
