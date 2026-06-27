<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\ClaimGuestAccountData;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Identity\Domain\Exceptions\EmailAlreadyTaken;
use Core\BoundedContext\Identity\Domain\Exceptions\UserNotFound;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PersonName;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * Completes the guest-claim flow (D1): fills real name, email and password over
 * an existing (guest) user id, instead of creating a brand-new user. Email
 * uniqueness is enforced against any OTHER user.
 */
final readonly class ClaimGuestAccount
{
    public function __construct(
        private UserRepository $repository,
        private PasswordHasher $hasher,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(ClaimGuestAccountData $data): UserResponse
    {
        $id = UserId::from($data->userId);
        $email = EmailAddress::from($data->email);

        return $this->transaction->execute(function () use ($id, $email, $data): UserResponse {
            $user = $this->repository->find($id);

            if ($user === null) {
                throw UserNotFound::withId($id);
            }

            $owner = $this->repository->findByEmail($email);

            if ($owner !== null && ! $owner->userId()->equals($id)) {
                throw EmailAlreadyTaken::for($email);
            }

            $user->claim(
                name: PersonName::from($data->name),
                email: $email,
                plain: PlainPassword::from($data->password),
                hasher: $this->hasher,
            );

            $this->repository->save($user);

            return UserResponse::fromUser($user);
        });
    }
}
