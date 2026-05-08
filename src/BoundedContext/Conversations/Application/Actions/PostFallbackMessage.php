<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\DTOs\PostFallbackMessageData;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;

final readonly class PostFallbackMessage
{
    public const FALLBACK_BODY = 'Lo siento, no puedo continuar esta conversación. Ha sido cerrada por motivos de seguridad.';

    public function __construct(private MessageRepository $messages) {}

    public function __invoke(PostFallbackMessageData $data): void
    {
        $message = Message::postFallback(
            ConversationId::from($data->conversationId),
            MessageBody::from(self::FALLBACK_BODY),
        );

        $this->messages->save($message);
    }
}
