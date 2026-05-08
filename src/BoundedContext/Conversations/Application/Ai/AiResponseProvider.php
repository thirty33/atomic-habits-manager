<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Ai;

use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;

/**
 * Application port for "let the AI produce an assistant reply for this
 * user message in the context of this conversation".
 *
 * The concrete provider chooses the model, builds the prompt, decides
 * which tools to expose, runs the agent loop. Application only knows
 * the input (Conversation aggregate + last user message body) and the
 * output (the assistant body to persist).
 *
 * The adapter MUST NOT mutate the conversation or persist anything; the
 * Use Case is the only owner of write effects.
 */
interface AiResponseProvider
{
    public function respondTo(Conversation $conversation, MessageBody $userMessage): MessageBody;
}
