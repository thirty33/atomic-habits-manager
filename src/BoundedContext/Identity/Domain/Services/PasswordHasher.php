<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\Services;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\HashedPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;

interface PasswordHasher
{
    public function hash(PlainPassword $plain): HashedPassword;

    public function matches(PlainPassword $plain, HashedPassword $hashed): bool;

    public function needsRehash(HashedPassword $hashed): bool;
}
