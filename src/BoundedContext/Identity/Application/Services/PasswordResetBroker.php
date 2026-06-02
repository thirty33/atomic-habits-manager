<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Services;

use Core\BoundedContext\Identity\Domain\User;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;

interface PasswordResetBroker
{
    public function sendLink(EmailAddress $email): string;

    /**
     * @param  callable(User): void  $applyReset
     */
    public function consume(EmailAddress $email, string $token, PlainPassword $newPassword, callable $applyReset): string;
}
