<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\User;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PersonName;
use Core\BoundedContext\Subscriptions\Application\Subscription\Actions\StartFreeSubscription;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * Creates a guest auto-user (D1) and immediately starts its free subscription
 * (cross-BC composition in the application layer, via the Subscriptions use
 * case). The guest is active from the start so {@see \auth()->id()} resolves
 * even before the account is claimed.
 */
final readonly class RegisterGuestUser
{
    public function __construct(
        private UserRepository $repository,
        private PasswordHasher $hasher,
        private StartFreeSubscription $startFreeSubscription,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(): UserResponse
    {
        return $this->transaction->execute(function (): UserResponse {
            $user = User::registerGuest(
                placeholderName: PersonName::from('Invitado'),
                temporaryEmail: $this->temporaryEmail(),
                hasher: $this->hasher,
            );

            $this->repository->save($user);

            $response = UserResponse::fromUser($user);

            ($this->startFreeSubscription)($response->userId);

            return $response;
        });
    }

    private function temporaryEmail(): EmailAddress
    {
        return EmailAddress::from(sprintf('guest_%s@guest.local', bin2hex(random_bytes(8))));
    }
}
