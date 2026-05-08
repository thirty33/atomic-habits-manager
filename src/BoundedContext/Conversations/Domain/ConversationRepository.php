<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain;

use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

/**
 * Persistence port for the Conversation aggregate.
 *
 * Lives in Domain — describes what the domain needs, not how it is
 * persisted. Implementation (adapter) lives in Infrastructure and
 * translates to Eloquent / SQL.
 *
 * Purity rules: zero imports from Illuminate, App, or any external layer.
 * Only Domain types and PHP primitives.
 */
interface ConversationRepository
{
    /**
     * Persist a Conversation (insert if new, update otherwise). When the
     * aggregate is new, the implementation must call assignId(...) and
     * the corresponding `record*AfterAssign()` method so domain events
     * carry the persisted id.
     */
    public function save(Conversation $conversation): void;

    /**
     * Returns the Conversation with that id, or null if it does not exist
     * (excluding soft-deleted rows).
     */
    public function find(ConversationId $id): ?Conversation;

    /**
     * Same as find(), but additionally requires ownership by the user.
     * Returns null if the conversation does not exist or is not owned by
     * the user — the caller cannot distinguish those two outcomes (404
     * shape) on purpose.
     */
    public function findForUser(ConversationId $id, UserId $userId): ?Conversation;
}
