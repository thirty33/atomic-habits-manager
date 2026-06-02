<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Services;

use Core\BoundedContext\Identity\Domain\User;

interface EmailVerificationNotifier
{
    public function send(User $user): void;
}
