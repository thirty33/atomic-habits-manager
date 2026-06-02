<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Identity\Application\Services\EmailVerificationNotifier;
use Core\BoundedContext\Identity\Application\Services\LoginThrottle;
use Core\BoundedContext\Identity\Application\Services\PasswordResetBroker;
use Core\BoundedContext\Identity\Application\Services\SessionAuthenticator;
use Core\BoundedContext\Identity\Domain\Events\UserEmailWasVerified;
use Core\BoundedContext\Identity\Domain\Events\UserHasLoggedIn;
use Core\BoundedContext\Identity\Domain\Events\UserHasLoggedOut;
use Core\BoundedContext\Identity\Domain\Events\UserPasswordWasChanged;
use Core\BoundedContext\Identity\Domain\Events\UserPasswordWasReset;
use Core\BoundedContext\Identity\Domain\Events\UserWasLockedOut;
use Core\BoundedContext\Identity\Domain\Events\UserWasRegistered;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Infrastructure\Auth\BreezeEmailVerificationNotifier;
use Core\BoundedContext\Identity\Infrastructure\Auth\BreezeLoginThrottle;
use Core\BoundedContext\Identity\Infrastructure\Auth\BreezePasswordResetBroker;
use Core\BoundedContext\Identity\Infrastructure\Auth\BreezeSessionAuthenticator;
use Core\BoundedContext\Identity\Infrastructure\Hashing\LaravelHasher;
use Core\BoundedContext\Identity\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        PasswordHasher::class => LaravelHasher::class,
        SessionAuthenticator::class => BreezeSessionAuthenticator::class,
        PasswordResetBroker::class => BreezePasswordResetBroker::class,
        LoginThrottle::class => BreezeLoginThrottle::class,
        EmailVerificationNotifier::class => BreezeEmailVerificationNotifier::class,
    ];

    public function register(): void
    {
        // El adapter de email verification necesita el UserProvider del guard 'web'.
        // Se resuelve dinámicamente cuando el container crea el notifier.
        $this->app->when(BreezeEmailVerificationNotifier::class)
            ->needs(UserProvider::class)
            ->give(fn ($app) => $app->make(AuthFactory::class)->guard('web')->getProvider());
    }

    public function boot(): void
    {
        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(UserWasRegistered::eventName(), UserWasRegistered::class);
        $registry->register(UserHasLoggedIn::eventName(), UserHasLoggedIn::class);
        $registry->register(UserHasLoggedOut::eventName(), UserHasLoggedOut::class);
        $registry->register(UserEmailWasVerified::eventName(), UserEmailWasVerified::class);
        $registry->register(UserPasswordWasReset::eventName(), UserPasswordWasReset::class);
        $registry->register(UserPasswordWasChanged::eventName(), UserPasswordWasChanged::class);
        $registry->register(UserWasLockedOut::eventName(), UserWasLockedOut::class);
    }
}
