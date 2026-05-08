<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;

/**
 * Estado que viaja entre pipes. Mutable a propósito — es la convención
 * del patrón `Illuminate\Pipeline`: cada pipe lee del passable, hace su
 * trabajo, deja sus resultados y pasa al siguiente.
 *
 * El `conversationId` es el único campo de entrada (lo setea el Action
 * desde el DTO). Los demás se llenan a medida que el pipeline avanza:
 *
 *  - `conversation`: cargado por LoadAndValidateConversationPipe.
 *  - `userMessage`:  el último mensaje del usuario, cargado por
 *                    LoadLatestUserMessagePipe.
 *  - `assistantBody`: respuesta del LLM principal, escrita por
 *                     GenerateAssistantReplyPipe.
 *  - `assistantMessage`: agregado persistido (status Pending), escrito
 *                        por PersistPendingAssistantMessagePipe.
 */
final class HandleAiResponsePassable
{
    public ?Conversation $conversation = null;

    public ?Message $userMessage = null;

    public ?MessageBody $assistantBody = null;

    public ?Message $assistantMessage = null;

    public function __construct(
        public readonly int $conversationId,
    ) {}
}
