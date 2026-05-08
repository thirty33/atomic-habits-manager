<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\ReadModels;

/**
 * Read-DTO for a Message: serializable, body-sanitized shape the
 * frontend consumes (chat page render + websocket broadcasts).
 *
 * Mirrors the legacy MessageResource shape so the JS layer stays
 * unchanged. The body can be sanitized at construction; the field is
 * already plain text by the time it lands here.
 */
final readonly class MessageSnapshot
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $messageId,
        public int $conversationId,
        public string $role,
        public string $type,
        public ?string $body,
        public ?string $mediaUrl,
        public string $status,
        public ?array $metadata,
        public string $createdAtHm,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'role' => $this->role,
            'type' => $this->type,
            'body' => $this->body,
            'media_url' => $this->mediaUrl,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAtHm,
        ];
    }
}
