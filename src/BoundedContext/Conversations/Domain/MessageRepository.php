<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain;

use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageId;

/**
 * Persistence port for the Message aggregate.
 */
interface MessageRepository
{
    /**
     * Persist a Message (insert if new, update otherwise). On insert the
     * implementation must assign the generated id and record the matching
     * "*WasPosted" event so downstream listeners see the persisted id.
     */
    public function save(Message $message): void;

    /**
     * Returns the Message by id, or null if it does not exist.
     */
    public function find(MessageId $id): ?Message;

    /**
     * Returns ALL messages of the conversation in created_at ASC order.
     * Use for the chat view; for context window slicing the agent layer
     * crops the tail in-memory.
     */
    public function findByConversation(ConversationId $conversationId): Messages;

    /**
     * Returns the latest message in the conversation by message_id DESC,
     * or null if the conversation has no messages.
     */
    public function latestForConversation(ConversationId $conversationId): ?Message;

    /**
     * Returns the most recent user-message body in the conversation, or
     * null if there is none. Used to build the moderation prompt.
     */
    public function lastUserMessageBody(ConversationId $conversationId): ?MessageBody;

    /**
     * Returns all assistant messages currently in Pending status —
     * candidates for moderation.
     */
    public function pendingAssistantMessages(): Messages;
}
