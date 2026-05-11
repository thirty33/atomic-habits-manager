<?php

declare(strict_types=1);

namespace Core\Shared\Application\Persistence;

/**
 * Application port for running a unit of work atomically. Use Cases that
 * coordinate multiple aggregates declare a transactional boundary by
 * calling execute(); the concrete implementation (Eloquent, in-memory,
 * etc.) lives in Infrastructure.
 *
 * Repositories should not open transactions themselves — that is the
 * responsibility of the Application layer (see PoEAA cap. 13, "Repository
 * + Unit of Work"). This contract is the Application-facing face of the
 * Unit of Work transactional boundary.
 */
interface TransactionManager
{
    /**
     * Run the given work inside a single transaction. Commits on normal
     * return, rolls back on any thrown Throwable (the exception is
     * re-thrown after rollback).
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public function execute(callable $work): mixed;
}
