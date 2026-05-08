<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Ai;

use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;

/**
 * Application port for "let the moderator AI evaluate this assistant
 * reply (in the context of the user message that prompted it) and apply
 * its decision".
 *
 * The concrete provider invokes the LLM moderator and, when the result
 * is approve-or-ban, calls the matching Application Use Case
 * (ApproveAssistantMessage / BanAssistantMessage). Application only
 * sees the orchestration call; the SDK lives in Infrastructure.
 */
interface AiModerationProvider
{
    public function moderate(Message $assistantMessage, ?MessageBody $userMessage): void;
}
