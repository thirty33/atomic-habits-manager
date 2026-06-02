<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Infrastructure\Auth;

use Core\BoundedContext\Identity\Application\Services\LoginThrottle;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class BreezeLoginThrottle implements LoginThrottle
{
    public function __construct(
        private RateLimiter $limiter,
        private Dispatcher $events,
        private Request $request,
    ) {}

    public function guard(EmailAddress $email, string $ipAddress): void
    {
        $key = $this->key($email, $ipAddress);

        if (! $this->limiter->tooManyAttempts($key, 5)) {
            return;
        }

        $this->events->dispatch(new Lockout($this->request));
        $seconds = $this->limiter->availableIn($key);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function hit(EmailAddress $email, string $ipAddress): void
    {
        $this->limiter->hit($this->key($email, $ipAddress));
    }

    public function clear(EmailAddress $email, string $ipAddress): void
    {
        $this->limiter->clear($this->key($email, $ipAddress));
    }

    private function key(EmailAddress $email, string $ipAddress): string
    {
        return Str::transliterate(strtolower($email->value()).'|'.$ipAddress);
    }
}
