<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\DTOs\BanAssistantMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\BanConversationData;
use Core\BoundedContext\Conversations\Domain\Exceptions\MessageNotFound;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;

final readonly class BanAssistantMessage
{
    public function __construct(
        private MessageRepository $messages,
        private BanConversation $banConversation,
    ) {}

    public function __invoke(BanAssistantMessageData $data): void
    {
        $message = $this->messages->find(MessageId::from($data->messageId));

        if ($message === null) {
            throw MessageNotFound::withId($data->messageId);
        }

        if ($message->status() !== MessageStatus::Pending) {
            return;
        }

        $message->ban($data->reason);
        $this->messages->save($message);

        // Cascade: a banned assistant message implies a banned
        // conversation. Cohesion is explicit here, not hidden inside a
        // tool. The conversation ban is itself idempotent.
        ($this->banConversation)(new BanConversationData(
            conversationId: $data->conversationId,
            reason: $data->reason,
        ));
    }
}
