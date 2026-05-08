<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain;

use Core\BoundedContext\Conversations\Application\ReadModels\ConversationSnapshot;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

/**
 * Read-side port for the chat page. Produces ReadModels (snapshots),
 * never aggregates — read-paths skip aggregate hydration on purpose.
 *
 * Separated from ConversationRepository (the write-side port) to avoid
 * the classic "hydrate the aggregate just to read one field" trap.
 */
interface ConversationReader
{
    /**
     * Conversations summary list for the user, ordered by last_message_at
     * DESC. Each entry has empty messages — only the lightweight preview
     * is populated.
     *
     * @return list<ConversationSnapshot>
     */
    public function listForUser(UserId $userId): array;

    /**
     * Conversation with all its messages in chronological order, scoped
     * to the user. Returns null when not found or not owned.
     */
    public function findForUserWithMessages(int $conversationId, UserId $userId): ?ConversationSnapshot;

    /**
     * Most-recent conversation for the user, with messages. Returns null
     * when the user has no conversations yet.
     */
    public function latestForUserWithMessages(UserId $userId): ?ConversationSnapshot;
}
