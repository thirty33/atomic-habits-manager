<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent;

use App\Models\Conversation as ConversationModel;
use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Illuminate\Support\Facades\DB;

final readonly class EloquentConversationRepository implements ConversationRepository
{
    public function __construct(
        private ConversationModel $model,
        private DomainEventBus $bus,
    ) {}

    public function save(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation) {
            $isNew = $conversation->isNew();

            $row = $isNew
                ? $this->model->newInstance()
                : $this->model->newQuery()->find($conversation->conversationId()->value());

            $row->fill($this->toAttributes($conversation));
            $row->save();

            if ($isNew) {
                $conversation->assignId(ConversationId::from((int) $row->getKey()));
                $conversation->recordStartedAfterAssign();
            }

            $this->bus->publish(...$conversation->pullDomainEvents());
        });
    }

    public function find(ConversationId $id): ?Conversation
    {
        $row = $this->model->newQuery()->find($id->value());

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findForUser(ConversationId $id, UserId $userId): ?Conversation
    {
        $row = $this->model->newQuery()
            ->where('conversation_id', $id->value())
            ->where('user_id', $userId->value())
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function delete(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation) {
            $id = $conversation->conversationId();

            if ($id === null) {
                throw new \LogicException('Cannot delete a Conversation without id.');
            }

            $this->model->newQuery()
                ->where('conversation_id', $id->value())
                ->delete();

            $this->bus->publish(...$conversation->pullDomainEvents());
        });
    }

    /**
     * @return list<int>
     */
    public function idsAwaitingAiResponse(): array
    {
        return $this->model->newQuery()
            ->where('status', \Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus::Active->value)
            ->whereHas('latestMessage', fn ($q) => $q->where('role', \Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole::User->value))
            ->pluck('conversation_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(Conversation $conversation): array
    {
        return [
            'user_id' => $conversation->userId()->value(),
            'title' => $conversation->title()->value,
            'status' => $conversation->status()->value,
            'last_message_at' => $conversation->lastMessageAt()->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(ConversationModel $row): Conversation
    {
        $a = $row->getAttributes();

        return Conversation::fromPrimitives(
            conversationId: (int) $a['conversation_id'],
            userId: (int) $a['user_id'],
            title: (string) $a['title'],
            status: (string) $a['status'],
            lastMessageAt: $this->stringOrNull($a['last_message_at'] ?? null),
            createdAt: $this->stringOrNull($a['created_at'] ?? null),
            updatedAt: $this->stringOrNull($a['updated_at'] ?? null),
            deletedAt: $this->stringOrNull($a['deleted_at'] ?? null),
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
