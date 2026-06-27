<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application;

use Core\BoundedContext\Identity\Application\DTOs\ListUsersData;
use Core\BoundedContext\Identity\Application\Responses\UsersPaginatedResponse;

/**
 * CQRS read port for the Users module listing. Implemented by the same Eloquent
 * adapter as {@see \Core\BoundedContext\Identity\Domain\UserRepository}
 * (single Eloquent implements Repository + Reader, per the house style).
 */
interface UserReader
{
    public function paginate(ListUsersData $data): UsersPaginatedResponse;
}
