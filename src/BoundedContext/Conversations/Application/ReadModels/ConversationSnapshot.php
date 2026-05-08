<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\ReadModels;

/**
 * Read-DTO for the chat page: a Conversation summary plus, when loaded,
 * its full message list. Mirrors the legacy ConversationResource shape
 * so the JS layer stays unchanged.
 *
 * `messages` is an empty list for the listing view; populated for the
 * "currently selected conversation" view.
 */
final readonly class ConversationSnapshot
{
    /**
     * @param  list<MessageSnapshot>  $messages
     */
    public function __construct(
        public int $conversationId,
        public int $userId,
        public string $title,
        public string $status,
        public ?string $lastMessageAtIso,
        public ?string $lastMessagePreview,
        public array $messages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'title' => $this->title,
            'status' => $this->status,
            'last_message_at' => $this->lastMessageAtIso,
            'last_message_preview' => $this->lastMessagePreview,
            'messages' => array_map(
                static fn (MessageSnapshot $m) => $m->toArray(),
                $this->messages,
            ),
        ];
    }
}
