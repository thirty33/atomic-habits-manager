<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Closure;
use Core\BoundedContext\Conversations\Application\Actions\PostFallbackMessage;
use Core\BoundedContext\Conversations\Application\DTOs\PostFallbackMessageData;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\Shared\Application\Logging\Logger;

/**
 * Cuerpo equivalente al `PostFallbackOnBan` listener.
 *
 * Re-fetchea el assistant message porque el pipe de moderación
 * (a través del tool del agente) puede haber cambiado su `status` a
 * Banned o Approved en DB. Si fue Banned → postear el fallback.
 *
 * Si fue Approved → no-op, el pipeline continúa hacia el broadcast.
 *
 * Reusamos `PostFallbackMessage` Use Case porque (a) es un Use Case
 * focalizado (no compone con otros), (b) es invocable también desde
 * tools/tests/otros entry points sin que sea anti-patrón, y (c) ya
 * encapsula `Message::postFallback` + persistencia con la firma del
 * fallback canónico.
 *
 * Si más adelante decidimos que el pipeline no debe consumir Use Cases
 * en absoluto, este pipe se reescribe inline con `Message::postFallback`
 * + `messages->save`. Por ahora es la opción de menor superficie.
 */
final readonly class PostFallbackIfBannedPipe
{
    public function __construct(
        private MessageRepository $messages,
        private PostFallbackMessage $postFallback,
        private Logger $logger,
    ) {}

    public function handle(HandleAiResponsePassable $passable, Closure $next): mixed
    {
        $this->logger->info('[ai-pipeline] 6.fallback_if_banned.enter', ['conversation_id' => $passable->conversationId]);

        $assistantMessageId = $passable->assistantMessage?->messageId();

        if ($assistantMessageId === null) {
            $this->logger->warning('[ai-pipeline] 6.fallback_if_banned.skip no_message_id', ['conversation_id' => $passable->conversationId]);

            return $next($passable);
        }

        $current = $this->messages->find($assistantMessageId);

        if ($current === null || $current->status() !== MessageStatus::Banned) {
            $this->logger->info('[ai-pipeline] 6.fallback_if_banned.skip not_banned', [
                'conversation_id' => $passable->conversationId,
                'message_id' => $assistantMessageId->value(),
                'status' => $current?->status()?->value,
            ]);

            return $next($passable);
        }

        $this->logger->info('[ai-pipeline] 6.fallback_if_banned.posting', [
            'conversation_id' => $passable->conversationId,
            'banned_message_id' => $assistantMessageId->value(),
        ]);
        ($this->postFallback)(new PostFallbackMessageData(
            conversationId: $passable->conversationId,
        ));

        return $next($passable);
    }
}
