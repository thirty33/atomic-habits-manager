<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Closure;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus;
use Core\Shared\Application\Logging\Logger;

/**
 * Cuerpo extraído del antiguo `ScheduleAiResponse` listener.
 *
 * Idempotencia por estado:
 *  - Si la conversación no existe, abortar el pipeline silenciosamente
 *    (probable retry tras hard-delete; nada que hacer).
 *  - Si la conversación ya no está Active (banned o archived), abortar
 *    silenciosamente: el sistema decidió que esa conversación no acepta
 *    más respuestas.
 *
 * Devolver `$passable` (en lugar de `$next($passable)`) corta la cadena
 * sin propagar excepción — convención de Pipeline para abort suave.
 */
final readonly class LoadAndValidateConversationPipe
{
    public function __construct(
        private ConversationRepository $conversations,
        private Logger $logger,
    ) {}

    public function handle(HandleAiResponsePassable $passable, Closure $next): mixed
    {
        $this->logger->info('[ai-pipeline] 1.load_conversation.enter', ['conversation_id' => $passable->conversationId]);

        $conversation = $this->conversations->find(
            ConversationId::from($passable->conversationId),
        );

        if ($conversation === null) {
            $this->logger->warning('[ai-pipeline] 1.load_conversation.abort not_found', ['conversation_id' => $passable->conversationId]);

            return $passable;
        }

        if ($conversation->status() !== ConversationStatus::Active) {
            $this->logger->warning('[ai-pipeline] 1.load_conversation.abort not_active', [
                'conversation_id' => $passable->conversationId,
                'status' => $conversation->status()->value,
            ]);

            return $passable;
        }

        $passable->conversation = $conversation;
        $this->logger->info('[ai-pipeline] 1.load_conversation.ok', ['conversation_id' => $passable->conversationId]);

        return $next($passable);
    }
}
