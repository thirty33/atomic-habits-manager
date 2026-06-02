<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Infrastructure\Auth;

use App\Models\User as UserModel;
use Core\BoundedContext\Identity\Application\Services\PasswordResetBroker;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;

final readonly class BreezePasswordResetBroker implements PasswordResetBroker
{
    public function __construct(
        private PasswordBrokerFactory $brokers,
        private UserRepository $users,
    ) {}

    public function sendLink(EmailAddress $email): string
    {
        return $this->brokers->broker()->sendResetLink(['email' => $email->value()]);
    }

    public function consume(EmailAddress $email, string $token, PlainPassword $newPassword, callable $applyReset): string
    {
        return $this->brokers->broker()->reset(
            [
                'email' => $email->value(),
                'password' => $newPassword->value(),
                'password_confirmation' => $newPassword->value(),
                'token' => $token,
            ],
            function (UserModel $row) use ($applyReset): void {
                $domainUser = $this->users->find(UserId::from((int) $row->getKey()));

                if ($domainUser === null) {
                    throw new \LogicException('User vanished between broker validation and reset callback.');
                }

                $applyReset($domainUser);
            },
        );
    }
}
