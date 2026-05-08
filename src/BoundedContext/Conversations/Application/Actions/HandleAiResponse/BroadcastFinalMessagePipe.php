<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Closure;
use Core\BoundedContext\Conversations\Application\Broadcasting\ConversationBroadcaster;
use Core\BoundedContext\Conversations\Application\ReadModels\MessageSnapshot;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\Shared\Application\Logging\Logger;

/**
 * Cuerpo equivalente a `BroadcastApprovedMessage` listener.
 *
 * Tras el pipeline:
 *  - Path feliz (approved): el assistant message original quedó approved.
 *  - Path baneo: el original quedó banned + se posteó un fallback nuevo
 *    que nace approved (ver `Message::postFallback`).
 *
 * En ambos casos, el ÚLTIMO mensaje de la conversación es un assistant
 * approved — eso es lo que la UI necesita ver. Por eso usamos
 * `latestForConversation` en lugar de re-fetch del agregado original.
 *
 * Si por alguna razón el último mensaje no es un assistant approved
 * (race condition rara, fallo a mitad de pipeline), no broadcasteamos —
 * la UI verá el cambio en el próximo refresh.
 */
final readonly class BroadcastFinalMessagePipe
{
    public function __construct(
        private MessageRepository $messages,
        private ConversationBroadcaster $broadcaster,
        private Logger $logger,
    ) {}

    public function handle(HandleAiResponsePassable $passable, Closure $next): mixed
    {
        $this->logger->info('[ai-pipeline] 7.broadcast.enter', ['conversation_id' => $passable->conversationId]);

        $final = $this->messages->latestForConversation(
            $passable->conversation->conversationId(),
        );

        if ($final === null
            || $final->role() !== MessageRole::Assistant
            || $final->status() !== MessageStatus::Approved
        ) {
            $this->logger->warning('[ai-pipeline] 7.broadcast.skip not_approved_assistant', [
                'conversation_id' => $passable->conversationId,
                'has_final' => $final !== null,
                'role' => $final?->role()?->value,
                'status' => $final?->status()?->value,
                'message_id' => $final?->messageId()?->value(),
            ]);

            return $next($passable);
        }

        $messageId = $final->messageId();
        if ($messageId === null) {
            $this->logger->warning('[ai-pipeline] 7.broadcast.skip no_message_id', ['conversation_id' => $passable->conversationId]);

            return $next($passable);
        }

        $snapshot = new MessageSnapshot(
            messageId: $messageId->value(),
            conversationId: $final->conversationId()->value(),
            role: $final->role()->value,
            type: $final->type()->value,
            body: $final->body()?->value,
            mediaUrl: $final->mediaUrl(),
            status: $final->status()->value,
            metadata: $final->metadata(),
            createdAtHm: $final->createdAt()?->format('H:i') ?? '',
        );

        $this->logger->info('[ai-pipeline] 7.broadcast.dispatching', [
            'conversation_id' => $passable->conversationId,
            'message_id' => $messageId->value(),
        ]);
        $this->broadcaster->messageReady(
            conversationId: $passable->conversationId,
            messagePayload: $snapshot->toArray(),
        );
        $this->logger->info('[ai-pipeline] 7.broadcast.ok', [
            'conversation_id' => $passable->conversationId,
            'message_id' => $messageId->value(),
        ]);

        return $next($passable);
    }
}
