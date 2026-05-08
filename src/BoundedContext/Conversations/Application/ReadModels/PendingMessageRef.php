<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\ReadModels;

/**
 * Pareja de identificadores (messageId, conversationId) que el cron de
 * moderación safety-net usa para invocar `ModerateAssistantMessage` sin
 * arrastrar la entidad Message completa hasta Infrastructure.
 *
 * Es un read-DTO mínimo — no expone body, role, ni status — porque ese
 * es el único contrato que el caller necesita.
 */
final readonly class PendingMessageRef
{
    public function __construct(
        public int $messageId,
        public int $conversationId,
    ) {}
}
