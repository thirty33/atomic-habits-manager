<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Primitives;

use Core\Shared\Domain\ValueObjects\ValueObject;

abstract class StringEnum extends ValueObject
{
    protected string $value;

    protected function __construct(string $value)
    {
        if (! in_array($value, $this->allowedValues(), true)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not valid. Allowed: %s',
                $value,
                implode(', ', $this->allowedValues())
            ));
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    abstract protected function allowedValues(): array;
}
