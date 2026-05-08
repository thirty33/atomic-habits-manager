<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\DTOs;

final readonly class PostUserMessageData
{
    public function __construct(
        public int $conversationId,
        public int $userId,
        public string $body,
    ) {}
}
