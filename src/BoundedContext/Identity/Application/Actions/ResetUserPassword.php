<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\ResetUserPasswordData;
use Core\BoundedContext\Identity\Application\Services\PasswordResetBroker;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\User;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;

final readonly class ResetUserPassword
{
    public function __construct(
        private PasswordResetBroker $broker,
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {}

    public function __invoke(ResetUserPasswordData $data): string
    {
        $email = EmailAddress::from($data->email);
        $newPassword = PlainPassword::from($data->password);

        return $this->broker->consume(
            $email,
            $data->token,
            $newPassword,
            function (User $user) use ($newPassword): void {
                $user->resetPassword($newPassword, $this->hasher);
                $this->users->save($user);
            },
        );
    }
}
