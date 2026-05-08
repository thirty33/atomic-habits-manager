<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\DTOs;

final readonly class ApproveAssistantMessageData
{
    public function __construct(
        public int $messageId,
        public ?string $reason = null,
    ) {}
}
