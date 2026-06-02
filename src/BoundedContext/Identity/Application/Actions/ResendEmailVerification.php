<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\Services\EmailVerificationNotifier;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class ResendEmailVerification
{
    public function __construct(
        private UserRepository $users,
        private EmailVerificationNotifier $notifier,
    ) {}

    public function __invoke(int $userId): bool
    {
        $user = $this->users->find(UserId::from($userId));

        if ($user === null || $user->isVerified()) {
            return false;
        }

        $this->notifier->send($user);

        return true;
    }
}
