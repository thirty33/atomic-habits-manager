<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Responses;

final readonly class AuthenticatedUserResponse
{
    public function __construct(
        public UserResponse $user,
        public string $redirectUrl,
    ) {}
}
