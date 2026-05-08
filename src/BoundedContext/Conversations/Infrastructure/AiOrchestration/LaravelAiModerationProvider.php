<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration;

use Core\BoundedContext\Conversations\Application\Actions\ApproveAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\BanAssistantMessage;
use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Agents\ModeratorAgent;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\ModerateMessageTool;

/**
 * Laravel\Ai SDK adapter for the AiModerationProvider Application port.
 *
 * Builds the ModeratorAgent and the single ModerateMessageTool on demand
 * (the tool needs the message and conversation ids as constructor
 * params), then prompts the LLM with the user-message + assistant-reply
 * pair. The tool itself dispatches the Approve / Ban Use Cases — this
 * adapter does not mutate state directly.
 */
final readonly class LaravelAiModerationProvider implements AiModerationProvider
{
    public function __construct(
        private string $provider,
        private string $model,
        private ApproveAssistantMessage $approve,
        private BanAssistantMessage $ban,
    ) {}

    public function moderate(Message $assistantMessage, ?MessageBody $userMessage): void
    {
        $messageId = $assistantMessage->messageId();

        if ($messageId === null) {
            return;
        }

        $tool = new ModerateMessageTool(
            messageId: $messageId->value(),
            conversationId: $assistantMessage->conversationId()->value(),
            approve: $this->approve,
            ban: $this->ban,
        );

        $agent = new ModeratorAgent(tools: [$tool]);

        $prompt = implode("\n\n", [
            'Mensaje del usuario:',
            $userMessage?->value ?? '',
            'Respuesta del asistente:',
            $assistantMessage->body()?->value ?? '',
        ]);

        $agent->prompt(
            $prompt,
            provider: $this->provider,
            model: $this->model,
        );
    }
}
