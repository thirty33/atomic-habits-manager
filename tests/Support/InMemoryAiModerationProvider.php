<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\BoundedContext\Conversations\Application\Actions\ApproveAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\BanAssistantMessage;
use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Application\DTOs\ApproveAssistantMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\BanAssistantMessageData;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;

/**
 * Test double for AiModerationProvider that follows a scripted decision
 * (approve / ban + reason) instead of calling the real LLM. Drives the
 * Approve / Ban Use Cases directly so the moderation flow runs end to
 * end in tests without an LLM round-trip.
 */
final class InMemoryAiModerationProvider implements AiModerationProvider
{
    public string $decision = 'approve';

    public ?string $reason = null;

    /** @var list<array{message_id: int, conversation_id: int, user_message: ?string, body: ?string}> */
    public array $calls = [];

    public function __construct(
        private readonly ApproveAssistantMessage $approve,
        private readonly BanAssistantMessage $ban,
    ) {}

    public function moderate(Message $assistantMessage, ?MessageBody $userMessage): void
    {
        $messageId = $assistantMessage->messageId();
        if ($messageId === null) {
            return;
        }

        $this->calls[] = [
            'message_id' => $messageId->value(),
            'conversation_id' => $assistantMessage->conversationId()->value(),
            'user_message' => $userMessage?->value,
            'body' => $assistantMessage->body()?->value,
        ];

        if ($this->decision === 'approve') {
            ($this->approve)(new ApproveAssistantMessageData(
                messageId: $messageId->value(),
                reason: $this->reason,
            ));

            return;
        }

        ($this->ban)(new BanAssistantMessageData(
            messageId: $messageId->value(),
            conversationId: $assistantMessage->conversationId()->value(),
            reason: $this->reason,
        ));
    }
}
