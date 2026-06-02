<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\DTOs;

final readonly class ConfirmUserPasswordData
{
    public function __construct(
        public int $userId,
        public string $password,
    ) {}
}
