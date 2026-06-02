<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\RegisterUserData;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Identity\Domain\Exceptions\EmailAlreadyTaken;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\User;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PersonName;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;

final readonly class RegisterUser
{
    public function __construct(
        private UserRepository $repository,
        private PasswordHasher $hasher,
    ) {}

    public function __invoke(RegisterUserData $data): UserResponse
    {
        $email = EmailAddress::from($data->email);

        if ($this->repository->emailExists($email)) {
            throw EmailAlreadyTaken::for($email);
        }

        $user = User::register(
            name: PersonName::from($data->name),
            email: $email,
            plain: PlainPassword::from($data->password),
            hasher: $this->hasher,
        );

        $this->repository->save($user);

        return UserResponse::fromUser($user);
    }
}
