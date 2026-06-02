<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\ValueObject;

final class EmailAddress extends ValueObject
{
    private string $value;

    protected function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            throw new \InvalidArgumentException('Email cannot be empty.');
        }

        if (mb_strlen($normalized) > 255) {
            throw new \InvalidArgumentException('Email cannot exceed 255 characters.');
        }

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid email.', $value));
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }
}
