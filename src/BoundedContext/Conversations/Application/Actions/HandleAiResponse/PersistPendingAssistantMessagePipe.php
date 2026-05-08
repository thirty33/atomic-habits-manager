<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Closure;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\Shared\Application\Logging\Logger;
use DateTimeImmutable;

/**
 * Cuerpo extraído de `ProcessUserMessageWithAi::__invoke()` (líneas
 * 57-61 del Use Case original).
 *
 * Crea el `Message` del asistente como Pending (factory `postAssistant`),
 * lo persiste, y actualiza `last_message_at` de la conversación.
 *
 * El agregado emite `AssistantMessageWasPosted` al guardarse — sigue en
 * outbox para listeners externos (audit, métricas), aunque dentro del
 * camino crítico de respuesta el evento ya no dispara nada porque la
 * moderación corre como pipe sincrónico en este mismo pipeline.
 */
final readonly class PersistPendingAssistantMessagePipe
{
    public function __construct(
        private MessageRepository $messages,
        private ConversationRepository $conversations,
        private Logger $logger,
    ) {}

    public function handle(HandleAiResponsePassable $passable, Closure $next): mixed
    {
        $this->logger->info('[ai-pipeline] 4.persist_pending.enter', ['conversation_id' => $passable->conversationId]);

        $assistant = Message::postAssistant(
            $passable->conversation->conversationId(),
            $passable->assistantBody,
        );

        $this->messages->save($assistant);

        $passable->conversation->touchLastMessageAt(new DateTimeImmutable);
        $this->conversations->save($passable->conversation);

        $passable->assistantMessage = $assistant;
        $this->logger->info('[ai-pipeline] 4.persist_pending.ok', [
            'conversation_id' => $passable->conversationId,
            'message_id' => $assistant->messageId()?->value(),
        ]);

        return $next($passable);
    }
}
