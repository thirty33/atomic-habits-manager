<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Services;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

interface SessionAuthenticator
{
    public function login(UserId $userId, bool $remember = false): void;

    public function logout(): void;

    public function markPasswordConfirmed(): void;

    public function invalidateSession(): void;
}
