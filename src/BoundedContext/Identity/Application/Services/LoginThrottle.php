<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Services;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;

interface LoginThrottle
{
    public function guard(EmailAddress $email, string $ipAddress): void;

    public function hit(EmailAddress $email, string $ipAddress): void;

    public function clear(EmailAddress $email, string $ipAddress): void;
}
