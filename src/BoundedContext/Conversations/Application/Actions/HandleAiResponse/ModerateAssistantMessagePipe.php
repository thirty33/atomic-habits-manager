<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Closure;
use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\Shared\Application\Logging\Logger;
use Throwable;

/**
 * Cuerpo extraído de `ModerateAssistantMessage::__invoke()`.
 *
 * Llama al `AiModerationProvider` (port Application). El adapter
 * `LaravelAiModerationProvider` arma el `ModeratorAgent` con su única
 * tool (`ModerateMessageTool`) y hace la llamada al LLM. La tool
 * decide aprobar o banear y, durante el ciclo del agente, invoca los
 * Use Cases `ApproveAssistantMessage` o `BanAssistantMessage` — eso NO
 * es "Use Case dentro de Use Case" porque el invocador es un Tool del
 * SDK (Infrastructure), no este pipe.
 *
 * Idempotencia por estado:
 *  - Si el assistant message ya no está Pending (otro intento ya lo
 *    moderó), abortar silenciosamente.
 *  - El check de `role === Assistant` es defensivo; el pipe anterior
 *    persiste solo mensajes de asistente.
 *
 * Tras este pipe, `$passable->assistantMessage` ya NO refleja el estado
 * actual del agregado — la tool del moderador modificó la fila en DB.
 * Los pipes siguientes (PostFallbackIfBanned, BroadcastFinal) hacen
 * re-fetch para ver el estado real.
 */
final readonly class ModerateAssistantMessagePipe
{
    public function __construct(
        private MessageRepository $messages,
        private AiModerationProvider $aiModerator,
        private Logger $logger,
    ) {}

    public function handle(HandleAiResponsePassable $passable, Closure $next): mixed
    {
        $this->logger->info('[ai-pipeline] 5.moderate.enter', ['conversation_id' => $passable->conversationId]);

        $assistantMessage = $passable->assistantMessage;

        if ($assistantMessage === null
            || $assistantMessage->status() !== MessageStatus::Pending
            || $assistantMessage->role() !== MessageRole::Assistant
        ) {
            $this->logger->warning('[ai-pipeline] 5.moderate.skip not_pending_assistant', [
                'conversation_id' => $passable->conversationId,
                'has_message' => $assistantMessage !== null,
                'status' => $assistantMessage?->status()?->value,
                'role' => $assistantMessage?->role()?->value,
            ]);

            return $next($passable);
        }

        $userMessageBody = $this->messages->lastUserMessageBody(
            $passable->conversation->conversationId(),
        );

        $started = microtime(true);
        try {
            $this->aiModerator->moderate($assistantMessage, $userMessageBody);
        } catch (Throwable $e) {
            $this->logger->error('[ai-pipeline] 5.moderate.threw', [
                'conversation_id' => $passable->conversationId,
                'message_id' => $assistantMessage->messageId()?->value(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'duration_s' => round(microtime(true) - $started, 2),
            ]);
            throw $e;
        }

        $refetched = $this->messages->find($assistantMessage->messageId());
        $this->logger->info('[ai-pipeline] 5.moderate.ok', [
            'conversation_id' => $passable->conversationId,
            'message_id' => $assistantMessage->messageId()?->value(),
            'final_status' => $refetched?->status()?->value,
            'duration_s' => round(microtime(true) - $started, 2),
        ]);

        return $next($passable);
    }
}
