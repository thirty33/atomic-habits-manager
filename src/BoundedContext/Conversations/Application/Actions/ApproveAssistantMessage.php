<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\DTOs\ApproveAssistantMessageData;
use Core\BoundedContext\Conversations\Domain\Exceptions\MessageNotFound;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;

final readonly class ApproveAssistantMessage
{
    public function __construct(private MessageRepository $messages) {}

    public function __invoke(ApproveAssistantMessageData $data): void
    {
        $message = $this->messages->find(MessageId::from($data->messageId));

        if ($message === null) {
            throw MessageNotFound::withId($data->messageId);
        }

        // Idempotency by state: already approved/banned messages are no-ops.
        if ($message->status() !== MessageStatus::Pending) {
            return;
        }

        $message->approve($data->reason);
        $this->messages->save($message);
    }
}
