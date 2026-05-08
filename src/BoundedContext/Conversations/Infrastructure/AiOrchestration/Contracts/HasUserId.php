<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

/**
 * Marker for Agents that expose audit-context metadata. Currently only
 * consumed by LogAiInvocationListener (Infrastructure event listener
 * for the SDK's AgentPrompted event).
 *
 * Will be renamed to HasAuditContext in flow 11 — kept here for the
 * staged migration so the listener keeps working.
 */
interface HasUserId
{
    public function userId(): int;
}
