<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\ReadModels\PendingMessageRef;
use Core\BoundedContext\Conversations\Domain\MessageRepository;

/**
 * Query-side Use Case: lista los mensajes del asistente que siguen en
 * estado Pending. Lo consume el cron `atomic-ia:moderate` (safety net)
 * cuando un mensaje no fue moderado por el listener
 * `ModerateAssistantMessageOnPost` — por error transitorio, deploy con
 * outbox congelado, etc.
 *
 * Devuelve `PendingMessageRef` (ids primitivos) en lugar de la entidad
 * `Message` para que el caller — un command de Infrastructure — no se
 * acople a Domain.
 */
final readonly class ListPendingAssistantMessages
{
    public function __construct(
        private MessageRepository $messages,
    ) {}

    /**
     * @return list<PendingMessageRef>
     */
    public function execute(): array
    {
        $refs = [];
        foreach ($this->messages->pendingAssistantMessages()->items() as $message) {
            $messageId = $message->messageId();
            if ($messageId === null) {
                continue;
            }

            $refs[] = new PendingMessageRef(
                messageId: $messageId->value(),
                conversationId: $message->conversationId()->value(),
            );
        }

        return $refs;
    }
}
