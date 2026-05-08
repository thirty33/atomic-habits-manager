<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Closure;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\Shared\Application\Logging\Logger;

/**
 * Cuerpo extraído de `ProcessUserMessageWithAi::__invoke()` (líneas
 * 49-53 del Use Case original).
 *
 * Idempotencia por estado: si el último mensaje de la conversación NO
 * es del usuario, significa que ya hay una respuesta del asistente en
 * curso (o ya completada). Abortar silenciosamente — el sistema ya hizo
 * o está haciendo el trabajo.
 *
 * Asume que `$passable->conversation` ya fue cargado por el pipe
 * anterior.
 */
final readonly class LoadLatestUserMessagePipe
{
    public function __construct(
        private MessageRepository $messages,
        private Logger $logger,
    ) {}

    public function handle(HandleAiResponsePassable $passable, Closure $next): mixed
    {
        $this->logger->info('[ai-pipeline] 2.load_user_message.enter', ['conversation_id' => $passable->conversationId]);

        $latest = $this->messages->latestForConversation(
            $passable->conversation->conversationId(),
        );

        if ($latest === null
            || $latest->role() !== MessageRole::User
            || $latest->body() === null
        ) {
            $this->logger->warning('[ai-pipeline] 2.load_user_message.abort latest_not_user', [
                'conversation_id' => $passable->conversationId,
                'has_message' => $latest !== null,
                'role' => $latest?->role()?->value,
                'has_body' => $latest?->body() !== null,
            ]);

            return $passable;
        }

        $passable->userMessage = $latest;
        $this->logger->info('[ai-pipeline] 2.load_user_message.ok', [
            'conversation_id' => $passable->conversationId,
            'message_id' => $latest->messageId()?->value(),
        ]);

        return $next($passable);
    }
}
