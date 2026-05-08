<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\DTOs;

final readonly class BanConversationData
{
    public function __construct(
        public int $conversationId,
        public ?string $reason = null,
    ) {}
}
