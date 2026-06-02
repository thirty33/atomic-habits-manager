<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\ChangeUserPasswordData;
use Core\BoundedContext\Identity\Domain\Exceptions\UserNotFound;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class ChangeUserPassword
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {}

    public function __invoke(ChangeUserPasswordData $data): void
    {
        $userId = UserId::from($data->userId);
        $user = $this->users->find($userId);

        if ($user === null) {
            throw UserNotFound::withId($userId);
        }

        $user->changePassword(
            currentPlain: PlainPassword::from($data->currentPassword),
            newPlain: PlainPassword::from($data->newPassword),
            hasher: $this->hasher,
        );

        $this->users->save($user);
    }
}
