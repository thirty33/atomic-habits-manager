<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Infrastructure\Hashing;

use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\HashedPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class LaravelHasher implements PasswordHasher
{
    public function __construct(private Hasher $hasher) {}

    public function hash(PlainPassword $plain): HashedPassword
    {
        return HashedPassword::from($this->hasher->make($plain->value()));
    }

    public function matches(PlainPassword $plain, HashedPassword $hashed): bool
    {
        return $this->hasher->check($plain->value(), $hashed->value());
    }

    public function needsRehash(HashedPassword $hashed): bool
    {
        return $this->hasher->needsRehash($hashed->value());
    }
}
