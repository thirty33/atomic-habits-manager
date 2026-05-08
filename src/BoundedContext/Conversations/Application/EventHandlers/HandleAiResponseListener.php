<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\EventHandlers;

use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponseAction;
use Core\BoundedContext\Conversations\Application\DTOs\HandleAiResponseData;
use Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted;

/**
 * Único listener async del flujo IA. Reacciona a `UserMessageWasPosted`
 * y delega al `HandleAiResponseAction` que orquesta todo el pipeline:
 * generar respuesta → persistir → moderar → fallback si banea →
 * broadcast.
 *
 * Reemplaza `ScheduleAiResponse`, `ModerateAssistantMessageOnPost`,
 * `PostFallbackOnBan` y `BroadcastApprovedMessage` (esos siguen en
 * disco como `.php.delete` para rollback rápido durante la verificación,
 * pero ya no están suscritos).
 *
 * `POLICY = 'heavy'` porque el pipeline incluye 2 round-trips al LLM
 * (generación + moderación) que pueden tomar minutos. El bucket Heavy
 * (tries=2, timeout=600) está dimensionado para esto.
 */
final readonly class HandleAiResponseListener
{
    public const POLICY = 'heavy';

    public function __construct(
        private HandleAiResponseAction $action,
    ) {}

    public function __invoke(UserMessageWasPosted $event): void
    {
        ($this->action)(new HandleAiResponseData(
            conversationId: $event->conversationId,
        ));
    }
}
