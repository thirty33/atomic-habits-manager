<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\Services\SessionAuthenticator;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class LogoutUser
{
    public function __construct(
        private UserRepository $users,
        private SessionAuthenticator $session,
    ) {}

    public function __invoke(int $userId): void
    {
        $user = $this->users->find(UserId::from($userId));

        if ($user !== null) {
            $user->logOut();
            $this->users->save($user);
        }

        $this->session->logout();
        $this->session->invalidateSession();
    }
}
