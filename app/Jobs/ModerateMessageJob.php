<?php

declare(strict_types=1);

namespace App\Jobs;

use Core\BoundedContext\Conversations\Application\Actions\ModerateAssistantMessage;
use Core\BoundedContext\Conversations\Application\DTOs\ModerateAssistantMessageData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ModerateMessageJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        public int $messageId,
        public int $conversationId,
    ) {}

    public function handle(ModerateAssistantMessage $useCase): void
    {
        $useCase(new ModerateAssistantMessageData(
            messageId: $this->messageId,
            conversationId: $this->conversationId,
        ));
    }
}
