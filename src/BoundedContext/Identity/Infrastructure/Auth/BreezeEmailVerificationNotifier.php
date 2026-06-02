<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Infrastructure\Auth;

use Core\BoundedContext\Identity\Application\Services\EmailVerificationNotifier;
use Core\BoundedContext\Identity\Domain\User as DomainUser;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;

final readonly class BreezeEmailVerificationNotifier implements EmailVerificationNotifier
{
    public function __construct(
        private UserProvider $userProvider,
        private NotificationDispatcher $notifications,
    ) {}

    public function send(DomainUser $user): void
    {
        $userId = $user->userId();

        if ($userId === null) {
            throw new \LogicException('Cannot notify an unsaved User.');
        }

        $notifiable = $this->userProvider->retrieveById($userId->value());

        if ($notifiable === null) {
            throw new \LogicException(sprintf('User %d not retrievable for verification.', $userId->value()));
        }

        $this->notifications->send($notifiable, new VerifyEmail);
    }
}
