<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Identity\Domain\Exceptions\UserNotFound;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Application\Persistence\TransactionManager;

final readonly class DeactivateUser
{
    public function __construct(
        private UserRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(int $userId): UserResponse
    {
        $id = UserId::from($userId);

        return $this->transaction->execute(function () use ($id): UserResponse {
            $user = $this->repository->find($id);

            if ($user === null) {
                throw UserNotFound::withId($id);
            }

            $user->deactivate();
            $this->repository->save($user);

            return UserResponse::fromUser($user);
        });
    }
}
