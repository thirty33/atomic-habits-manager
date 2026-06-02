<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\ConfirmUserPasswordData;
use Core\BoundedContext\Identity\Application\Services\SessionAuthenticator;
use Core\BoundedContext\Identity\Domain\Exceptions\InvalidCredentials;
use Core\BoundedContext\Identity\Domain\Exceptions\UserNotFound;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class ConfirmUserPassword
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private SessionAuthenticator $session,
    ) {}

    public function __invoke(ConfirmUserPasswordData $data): void
    {
        $userId = UserId::from($data->userId);
        $user = $this->users->find($userId);

        if ($user === null) {
            throw UserNotFound::withId($userId);
        }

        if (! $this->hasher->matches(PlainPassword::from($data->password), $user->password())) {
            throw InvalidCredentials::forPasswordChange($user->userId());
        }

        $this->session->markPasswordConfirmed();
    }
}
