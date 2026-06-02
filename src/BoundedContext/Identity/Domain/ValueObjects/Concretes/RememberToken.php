<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\ValueObject;

final class RememberToken extends ValueObject
{
    private string $value;

    protected function __construct(string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Remember token cannot be empty.');
        }

        if (mb_strlen($value) > 100) {
            throw new \InvalidArgumentException('Remember token cannot exceed 100 characters.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(30)));
    }
}
