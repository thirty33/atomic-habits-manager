<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent;

use App\Models\Conversation as ConversationModel;
use App\Models\Message as MessageModel;
use Core\BoundedContext\Conversations\Application\ConversationReader;
use Core\BoundedContext\Conversations\Application\ReadModels\ConversationSnapshot;
use Core\BoundedContext\Conversations\Application\ReadModels\MessageSnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Support\Str;

final readonly class EloquentConversationReader implements ConversationReader
{
    /**
     * @return list<ConversationSnapshot>
     */
    public function listForUser(UserId $userId): array
    {
        $rows = ConversationModel::query()
            ->where('user_id', $userId->value())
            ->with('latestMessage')
            ->orderByDesc('last_message_at')
            ->get();

        return $rows
            ->map(fn (ConversationModel $row) => new ConversationSnapshot(
                conversationId: (int) $row->conversation_id,
                userId: (int) $row->user_id,
                title: (string) ($row->title ?? ''),
                status: $this->statusValue($row),
                lastMessageAtIso: $row->last_message_at?->isoFormat('LL'),
                lastMessagePreview: $row->latestMessage !== null
                    ? Str::limit((string) $row->latestMessage->body, 60)
                    : null,
                messages: [],
            ))
            ->all();
    }

    public function findForUserWithMessages(int $conversationId, UserId $userId): ?ConversationSnapshot
    {
        $row = ConversationModel::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId->value())
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')->orderBy('message_id')])
            ->first();

        return $row !== null ? $this->toSnapshot($row) : null;
    }

    public function latestForUserWithMessages(UserId $userId): ?ConversationSnapshot
    {
        $row = ConversationModel::query()
            ->where('user_id', $userId->value())
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')->orderBy('message_id')])
            ->orderByDesc('last_message_at')
            ->first();

        return $row !== null ? $this->toSnapshot($row) : null;
    }

    private function toSnapshot(ConversationModel $row): ConversationSnapshot
    {
        $messages = $row->messages
            ->map(fn (MessageModel $m) => new MessageSnapshot(
                messageId: (int) $m->message_id,
                conversationId: (int) $m->conversation_id,
                role: $this->roleValue($m),
                type: (string) ($m->type ?? 'text'),
                body: $this->sanitizeBody($m->body),
                mediaUrl: $m->media_url,
                status: $this->messageStatusValue($m),
                metadata: $m->metadata,
                createdAtHm: $m->created_at?->format('H:i') ?? '',
            ))
            ->all();

        return new ConversationSnapshot(
            conversationId: (int) $row->conversation_id,
            userId: (int) $row->user_id,
            title: (string) ($row->title ?? ''),
            status: $this->statusValue($row),
            lastMessageAtIso: $row->last_message_at?->isoFormat('LL'),
            lastMessagePreview: null,
            messages: $messages,
        );
    }

    private function statusValue(ConversationModel $row): string
    {
        return $row->status instanceof \BackedEnum ? (string) $row->status->value : (string) $row->status;
    }

    private function messageStatusValue(MessageModel $m): string
    {
        return $m->status instanceof \BackedEnum ? (string) $m->status->value : (string) $m->status;
    }

    private function roleValue(MessageModel $m): string
    {
        return $m->role instanceof \BackedEnum ? (string) $m->role->value : (string) $m->role;
    }

    private function sanitizeBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $body = strip_tags($body);
        $body = preg_replace('/!\[.*?\]\(.*?\)/', '', $body) ?? '';

        return trim($body);
    }
}
