<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes;

use InvalidArgumentException;

final readonly class ConversationTitle
{
    private const MAX_LENGTH = 120;

    private const DEFAULT_VALUE = 'Nueva conversación';

    private function __construct(public string $value) {}

    public static function from(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('ConversationTitle cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'ConversationTitle exceeds %d chars.',
                self::MAX_LENGTH,
            ));
        }

        return new self($trimmed);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_VALUE);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
