<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\ValueObject;

final class HexColor extends ValueObject
{
    private string $value;

    protected function __construct(string $value)
    {
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid hex color. Expected format: #RRGGBB.',
                $value
            ));
        }

        $this->value = strtoupper($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
