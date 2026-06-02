<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\ValueObject;

final class PlainPassword extends ValueObject
{
    private string $value;

    protected function __construct(string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Password cannot be empty.');
        }

        if (mb_strlen($value) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }

        if (mb_strlen($value) > 4096) {
            throw new \InvalidArgumentException('Password is unreasonably long.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return '[REDACTED]';
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '[REDACTED]'];
    }
}
