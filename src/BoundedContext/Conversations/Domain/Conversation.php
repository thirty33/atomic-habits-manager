<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain;

use Core\BoundedContext\Conversations\Domain\Events\ConversationWasDeleted;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasStarted;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationTitle;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use LogicException;

/**
 * Aggregate root for the chat conversation between a user and the AI.
 *
 * Invariants enforced here:
 *  - Status moves Active → Archived | Banned. It does not move backwards.
 *  - last_message_at is monotonic forward.
 *  - Only the owning UserId can mutate the aggregate (enforced at the
 *    Use Case / Repository boundary, not here — the aggregate trusts its
 *    inputs once loaded).
 */
final class Conversation extends AggregateRoot
{
    private function __construct(
        private ?ConversationId $conversationId,
        private UserId $userId,
        private ConversationTitle $title,
        private ConversationStatus $status,
        private DateTimeImmutable $lastMessageAt,
        private ?DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {}

    public static function start(UserId $userId): self
    {
        $now = new DateTimeImmutable;

        return new self(
            conversationId: null,
            userId: $userId,
            title: ConversationTitle::default(),
            status: ConversationStatus::Active,
            lastMessageAt: $now,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
        );
    }

    public static function fromPrimitives(
        int $conversationId,
        int $userId,
        string $title,
        string $status,
        ?string $lastMessageAt,
        ?string $createdAt,
        ?string $updatedAt,
        ?string $deletedAt,
    ): self {
        return new self(
            conversationId: ConversationId::from($conversationId),
            userId: UserId::from($userId),
            title: ConversationTitle::from($title),
            status: ConversationStatus::from($status),
            lastMessageAt: $lastMessageAt !== null ? new DateTimeImmutable($lastMessageAt) : new DateTimeImmutable,
            createdAt: $createdAt !== null ? new DateTimeImmutable($createdAt) : null,
            updatedAt: $updatedAt !== null ? new DateTimeImmutable($updatedAt) : null,
            deletedAt: $deletedAt !== null ? new DateTimeImmutable($deletedAt) : null,
        );
    }

    public function assignId(ConversationId $id): void
    {
        if ($this->conversationId !== null) {
            throw new LogicException('Conversation already has id.');
        }

        $this->conversationId = $id;
    }

    /**
     * Records the ConversationWasStarted event after id assignment so the
     * outbox payload references the persisted id, not null.
     */
    public function recordStartedAfterAssign(): void
    {
        if ($this->conversationId === null) {
            throw new LogicException('Cannot record ConversationWasStarted before id assignment.');
        }

        $this->record(new ConversationWasStarted(
            conversationId: $this->conversationId->value(),
            userId: $this->userId->value(),
        ));
    }

    public function touchLastMessageAt(DateTimeImmutable $at): void
    {
        if ($at < $this->lastMessageAt) {
            return;
        }

        $this->lastMessageAt = $at;
    }

    public function delete(): void
    {
        if ($this->conversationId === null) {
            throw new LogicException('Cannot delete a Conversation that has no id.');
        }

        $this->record(new ConversationWasDeleted(
            conversationId: $this->conversationId->value(),
            userId: $this->userId->value(),
        ));
    }

    public function isNew(): bool
    {
        return $this->conversationId === null;
    }

    public function conversationId(): ?ConversationId
    {
        return $this->conversationId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function title(): ConversationTitle
    {
        return $this->title;
    }

    public function status(): ConversationStatus
    {
        return $this->status;
    }

    public function lastMessageAt(): DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
