<?php

namespace Core\Shared\Domain\ValueObjects\Primitives;

use Core\Shared\Domain\ValueObjects\ValueObject;

abstract class BoundedText extends ValueObject
{
    protected string $value;

    protected function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Value cannot be empty.');
        }

        if (mb_strlen($value) > $this->maxLength()) {
            throw new \InvalidArgumentException(sprintf(
                'Value cannot exceed %d characters, got %d.',
                $this->maxLength(),
                mb_strlen($value)
            ));
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    abstract protected function maxLength(): int;
}
