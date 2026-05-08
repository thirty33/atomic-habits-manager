<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain;

use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageType;
use Core\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use LogicException;

/**
 * Aggregate root for a single message inside a Conversation.
 *
 * Lifecycle invariants:
 *  - User messages are born Sent and never moderated.
 *  - Assistant messages are born Pending. Moderation transitions them to
 *    Approved or Banned. (Methods added in flow 06.)
 *  - Fallback messages (system-controlled apology after a ban) are born
 *    Approved without passing through moderation. (Method added in flow 07.)
 *
 * The Conversation/Message boundary is by id, not by pointer — Message
 * holds a ConversationId VO. Cross-aggregate consistency lives in Use
 * Cases, not inside the aggregate.
 *
 * Factories that create new instances (postUser, postAssistant,
 * postFallback) attach a PendingFactoryEvent marker. The repository
 * calls recordPendingFactoryEventAfterAssign() once the persisted id is
 * known so the corresponding Domain Event carries the real id.
 */
final class Message extends AggregateRoot
{
    private ?PendingFactoryEvent $pendingFactoryEvent = null;

    private function __construct(
        private ?MessageId $messageId,
        private ConversationId $conversationId,
        private MessageRole $role,
        private MessageType $type,
        private ?MessageBody $body,
        private ?string $mediaUrl,
        private MessageStatus $status,
        /** @var array<string, mixed> */
        private array $metadata,
        private ?DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
    ) {}

    public static function postUser(ConversationId $conversationId, MessageBody $body): self
    {
        $message = new self(
            messageId: null,
            conversationId: $conversationId,
            role: MessageRole::User,
            type: MessageType::Text,
            body: $body,
            mediaUrl: null,
            status: MessageStatus::Sent,
            metadata: [],
            createdAt: null,
            updatedAt: null,
        );
        $message->pendingFactoryEvent = PendingFactoryEvent::UserPosted;

        return $message;
    }

    public static function postAssistant(ConversationId $conversationId, MessageBody $body): self
    {
        $message = new self(
            messageId: null,
            conversationId: $conversationId,
            role: MessageRole::Assistant,
            type: MessageType::Text,
            body: $body,
            mediaUrl: null,
            status: MessageStatus::Pending,
            metadata: [],
            createdAt: null,
            updatedAt: null,
        );
        $message->pendingFactoryEvent = PendingFactoryEvent::AssistantPosted;

        return $message;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function fromPrimitives(
        int $messageId,
        int $conversationId,
        string $role,
        string $type,
        ?string $body,
        ?string $mediaUrl,
        string $status,
        ?array $metadata,
        ?string $createdAt,
        ?string $updatedAt,
    ): self {
        return new self(
            messageId: MessageId::from($messageId),
            conversationId: ConversationId::from($conversationId),
            role: MessageRole::from($role),
            type: MessageType::from($type),
            body: $body !== null && $body !== '' ? MessageBody::from($body) : null,
            mediaUrl: $mediaUrl,
            status: MessageStatus::from($status),
            metadata: $metadata ?? [],
            createdAt: $createdAt !== null ? new DateTimeImmutable($createdAt) : null,
            updatedAt: $updatedAt !== null ? new DateTimeImmutable($updatedAt) : null,
        );
    }

    public function assignId(MessageId $id): void
    {
        if ($this->messageId !== null) {
            throw new LogicException('Message already has id.');
        }

        $this->messageId = $id;
    }

    /**
     * Records the matching domain event (UserMessageWasPosted /
     * AssistantMessageWasPosted / FallbackMessageWasPosted) using the
     * persisted message id. Idempotent — does nothing when called on an
     * aggregate hydrated from primitives (no pending factory event).
     */
    public function recordPendingFactoryEventAfterAssign(): void
    {
        if ($this->pendingFactoryEvent === null) {
            return;
        }

        if ($this->messageId === null) {
            throw new LogicException('Cannot record factory event before id assignment.');
        }

        if ($this->body === null) {
            throw new LogicException('Factory events require a body.');
        }

        $event = match ($this->pendingFactoryEvent) {
            PendingFactoryEvent::UserPosted => new UserMessageWasPosted(
                messageId: $this->messageId->value(),
                conversationId: $this->conversationId->value(),
                body: $this->body->value,
            ),
            PendingFactoryEvent::AssistantPosted => new AssistantMessageWasPosted(
                messageId: $this->messageId->value(),
                conversationId: $this->conversationId->value(),
                body: $this->body->value,
            ),
        };

        $this->record($event);
        $this->pendingFactoryEvent = null;
    }

    public function isNew(): bool
    {
        return $this->messageId === null;
    }

    public function messageId(): ?MessageId
    {
        return $this->messageId;
    }

    public function conversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function role(): MessageRole
    {
        return $this->role;
    }

    public function type(): MessageType
    {
        return $this->type;
    }

    public function body(): ?MessageBody
    {
        return $this->body;
    }

    public function mediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function status(): MessageStatus
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
