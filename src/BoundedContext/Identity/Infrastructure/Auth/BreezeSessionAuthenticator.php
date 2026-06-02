<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Infrastructure\Auth;

use Core\BoundedContext\Identity\Application\Services\SessionAuthenticator;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Session\Session;

final readonly class BreezeSessionAuthenticator implements SessionAuthenticator
{
    public function __construct(
        private AuthFactory $auth,
        private Session $session,
    ) {}

    public function login(UserId $userId, bool $remember = false): void
    {
        $this->auth->guard('web')->loginUsingId($userId->value(), $remember);
        $this->session->regenerate();
    }

    public function logout(): void
    {
        $this->auth->guard('web')->logout();
    }

    public function invalidateSession(): void
    {
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    public function markPasswordConfirmed(): void
    {
        $this->session->put('auth.password_confirmed_at', time());
    }
}
