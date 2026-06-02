<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\ValueObject;

final class HashedPassword extends ValueObject
{
    private string $value;

    protected function __construct(string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Hashed password cannot be empty.');
        }

        if (! preg_match('/^\$(2y|argon2i|argon2id)\$/', $value)) {
            throw new \InvalidArgumentException('Hashed password format is not recognized.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return '[HASH]';
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '[HASH]'];
    }
}
