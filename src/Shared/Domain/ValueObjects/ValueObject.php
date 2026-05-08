<?php

namespace Core\Shared\Domain\ValueObjects;

abstract class ValueObject
{
    abstract public function value(): mixed;

    public static function from(mixed ...$values): static
    {
        return new static(...$values);
    }

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return (string) $this->value();
    }

    public function __set(string $name, mixed $value): void
    {
        throw new \RuntimeException('Value Objects are immutable.');
    }
}
