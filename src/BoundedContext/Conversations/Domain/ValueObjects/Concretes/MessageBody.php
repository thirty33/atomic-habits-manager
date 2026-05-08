<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes;

use InvalidArgumentException;

final readonly class MessageBody
{
    private const MAX_LENGTH = 5000;

    private function __construct(public string $value) {}

    public static function from(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('MessageBody cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'MessageBody exceeds %d chars.',
                self::MAX_LENGTH,
            ));
        }

        return new self($trimmed);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
