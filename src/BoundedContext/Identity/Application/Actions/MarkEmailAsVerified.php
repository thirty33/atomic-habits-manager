<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Domain\Exceptions\UserNotFound;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class MarkEmailAsVerified
{
    public function __construct(private UserRepository $users) {}

    public function __invoke(int $userId): bool
    {
        $id = UserId::from($userId);
        $user = $this->users->find($id);

        if ($user === null) {
            throw UserNotFound::withId($id);
        }

        $wasUnverified = ! $user->isVerified();

        $user->verifyEmail(new \DateTimeImmutable);
        $this->users->save($user);

        return $wasUnverified;
    }
}
