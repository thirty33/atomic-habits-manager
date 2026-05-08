<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent;

use App\Models\Message as MessageModel;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\Messages;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\Shared\Domain\Bus\DomainEventBus;
use Illuminate\Support\Facades\DB;

final readonly class EloquentMessageRepository implements MessageRepository
{
    public function __construct(
        private MessageModel $model,
        private DomainEventBus $bus,
    ) {}

    public function save(Message $message): void
    {
        DB::transaction(function () use ($message) {
            $isNew = $message->isNew();

            $row = $isNew
                ? $this->model->newInstance()
                : $this->model->newQuery()->find($message->messageId()->value());

            $row->fill($this->toAttributes($message));
            $row->save();

            if ($isNew) {
                $message->assignId(MessageId::from((int) $row->getKey()));
                $message->recordPendingFactoryEventAfterAssign();
            }

            $this->bus->publish(...$message->pullDomainEvents());
        });
    }

    public function find(MessageId $id): ?Message
    {
        $row = $this->model->newQuery()->find($id->value());

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findByConversation(ConversationId $conversationId): Messages
    {
        $rows = $this->model->newQuery()
            ->where('conversation_id', $conversationId->value())
            ->orderBy('created_at')
            ->orderBy('message_id')
            ->get();

        return new Messages($rows->map(fn (MessageModel $r) => $this->toDomain($r))->all());
    }

    public function latestForConversation(ConversationId $conversationId): ?Message
    {
        $row = $this->model->newQuery()
            ->where('conversation_id', $conversationId->value())
            ->orderByDesc('message_id')
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function lastUserMessageBody(ConversationId $conversationId): ?MessageBody
    {
        $body = $this->model->newQuery()
            ->where('conversation_id', $conversationId->value())
            ->where('role', MessageRole::User->value)
            ->orderByDesc('message_id')
            ->value('body');

        if ($body === null || $body === '') {
            return null;
        }

        return MessageBody::from((string) $body);
    }

    public function pendingAssistantMessages(): Messages
    {
        $rows = $this->model->newQuery()
            ->where('role', MessageRole::Assistant->value)
            ->where('status', MessageStatus::Pending->value)
            ->orderBy('message_id')
            ->get();

        return new Messages($rows->map(fn (MessageModel $r) => $this->toDomain($r))->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(Message $message): array
    {
        return [
            'conversation_id' => $message->conversationId()->value(),
            'role' => $message->role()->value,
            'type' => $message->type()->value,
            'body' => $message->body()?->value,
            'media_url' => $message->mediaUrl(),
            'status' => $message->status()->value,
            'metadata' => $message->metadata(),
        ];
    }

    private function toDomain(MessageModel $row): Message
    {
        $a = $row->getAttributes();

        $metadata = $a['metadata'] ?? null;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        return Message::fromPrimitives(
            messageId: (int) $a['message_id'],
            conversationId: (int) $a['conversation_id'],
            role: (string) $a['role'],
            type: (string) ($a['type'] ?? 'text'),
            body: $a['body'] ?? null,
            mediaUrl: $a['media_url'] ?? null,
            status: (string) $a['status'],
            metadata: $metadata,
            createdAt: isset($a['created_at']) ? (string) $a['created_at'] : null,
            updatedAt: isset($a['updated_at']) ? (string) $a['updated_at'] : null,
        );
    }
}
