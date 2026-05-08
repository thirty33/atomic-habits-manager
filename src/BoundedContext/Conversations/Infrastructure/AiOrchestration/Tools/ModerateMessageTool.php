<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools;

use Core\BoundedContext\Conversations\Application\Actions\ApproveAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\BanAssistantMessage;
use Core\BoundedContext\Conversations\Application\DTOs\ApproveAssistantMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\BanAssistantMessageData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Records the moderator's decision via the Application Use Cases. The
 * message id and conversation id travel by constructor — no auth() call,
 * no Eloquent lookup, no global state.
 */
final readonly class ModerateMessageTool implements Tool
{
    public function __construct(
        private int $messageId,
        private int $conversationId,
        private ApproveAssistantMessage $approve,
        private BanAssistantMessage $ban,
    ) {}

    public function description(): Stringable|string
    {
        return 'Registra la decisión de moderación del mensaje. Úsala siempre al finalizar tu evaluación.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'approved' => $schema->boolean()
                ->description('true si el mensaje es seguro, false si contiene una amenaza.')
                ->required(),
            'reason' => $schema->string()
                ->description('Razón del rechazo en español. Solo requerida si approved es false.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $approved = (bool) $request['approved'];
        $reason = $request['reason'] ?? null;

        if ($approved) {
            ($this->approve)(new ApproveAssistantMessageData(
                messageId: $this->messageId,
                reason: $reason,
            ));

            return 'Mensaje aprobado.';
        }

        ($this->ban)(new BanAssistantMessageData(
            messageId: $this->messageId,
            conversationId: $this->conversationId,
            reason: $reason,
        ));

        return 'Mensaje baneado y conversación cerrada.';
    }
}
