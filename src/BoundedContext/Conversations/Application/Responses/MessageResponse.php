<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Responses;

use Core\BoundedContext\Conversations\Domain\Message;

final readonly class MessageResponse
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $messageId,
        public int $conversationId,
        public string $role,
        public string $type,
        public ?string $body,
        public ?string $mediaUrl,
        public string $status,
        public array $metadata,
        public string $createdAtHm,
    ) {}

    public static function fromAggregate(Message $message): self
    {
        $id = $message->messageId();

        if ($id === null) {
            throw new \LogicException('Cannot build MessageResponse from an unsaved Message.');
        }

        return new self(
            messageId: $id->value(),
            conversationId: $message->conversationId()->value(),
            role: $message->role()->value,
            type: $message->type()->value,
            body: $message->body()?->value,
            mediaUrl: $message->mediaUrl(),
            status: $message->status()->value,
            metadata: $message->metadata(),
            createdAtHm: $message->createdAt()?->format('H:i') ?? '',
        );
    }

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
