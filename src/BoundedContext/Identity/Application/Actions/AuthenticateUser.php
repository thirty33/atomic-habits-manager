<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\AuthenticateUserData;
use Core\BoundedContext\Identity\Application\Responses\AuthenticatedUserResponse;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Identity\Application\Services\LoginThrottle;
use Core\BoundedContext\Identity\Application\Services\SessionAuthenticator;
use Core\BoundedContext\Identity\Domain\Exceptions\InvalidCredentials;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;

final readonly class AuthenticateUser
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private SessionAuthenticator $session,
        private LoginThrottle $throttle,
    ) {}

    public function __invoke(AuthenticateUserData $data): AuthenticatedUserResponse
    {
        $email = EmailAddress::from($data->email);
        $this->throttle->guard($email, $data->ipAddress);

        $user = $this->users->findActiveByEmail($email);

        if ($user === null) {
            $this->throttle->hit($email, $data->ipAddress);
            throw InvalidCredentials::forEmail($email);
        }

        try {
            $user->logIn(PlainPassword::from($data->password), $this->hasher);
        } catch (\Throwable $e) {
            $this->throttle->hit($email, $data->ipAddress);
            throw $e;
        }

        $this->users->save($user);
        $this->throttle->clear($email, $data->ipAddress);
        $this->session->login($user->userId(), $data->remember);

        return new AuthenticatedUserResponse(
            user: UserResponse::fromUser($user),
            redirectUrl: $user->isAdmin() ? route('backoffice.dashboard.index') : route('dashboard'),
        );
    }
}
