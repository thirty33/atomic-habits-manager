<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\ListUsersData;
use Core\BoundedContext\Identity\Application\Responses\UsersPaginatedResponse;
use Core\BoundedContext\Identity\Application\UserReader;

final readonly class ListUsers
{
    public function __construct(private UserReader $reader) {}

    public function __invoke(ListUsersData $data): UsersPaginatedResponse
    {
        return $this->reader->paginate($data);
    }
}
