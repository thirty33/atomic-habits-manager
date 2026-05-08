<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;

/**
 * Test double for AiResponseProvider that returns a canned body — never
 * touches the real LLM. Captures every prompt for assertions.
 */
final class InMemoryAiResponseProvider implements AiResponseProvider
{
    public string $cannedBody = 'Respuesta del asistente (test).';

    /** @var list<array{conversation_id: int, user_message: string}> */
    public array $calls = [];

    public function respondTo(Conversation $conversation, MessageBody $userMessage): MessageBody
    {
        $this->calls[] = [
            'conversation_id' => $conversation->conversationId()?->value() ?? 0,
            'user_message' => $userMessage->value,
        ];

        return MessageBody::from($this->cannedBody);
    }
}
