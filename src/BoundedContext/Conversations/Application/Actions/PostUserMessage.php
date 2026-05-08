<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\DTOs\PostUserMessageData;
use Core\BoundedContext\Conversations\Application\Responses\MessageResponse;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Exceptions\ConversationNotFound;
use Core\BoundedContext\Conversations\Domain\Message;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use DateTimeImmutable;

final readonly class PostUserMessage
{
    public function __construct(
        private ConversationRepository $conversations,
        private MessageRepository $messages,
    ) {}

    public function __invoke(PostUserMessageData $data): MessageResponse
    {
        $conversationId = ConversationId::from($data->conversationId);
        $userId = UserId::from($data->userId);

        $conversation = $this->conversations->findForUser($conversationId, $userId);

        if ($conversation === null) {
            throw ConversationNotFound::withId($data->conversationId);
        }

        $message = Message::postUser($conversationId, MessageBody::from($data->body));
        $this->messages->save($message);

        $conversation->touchLastMessageAt(new DateTimeImmutable);
        $this->conversations->save($conversation);

        return MessageResponse::fromAggregate($message);
    }
}
