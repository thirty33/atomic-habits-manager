<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\DTOs\DeleteConversationData;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Exceptions\ConversationNotFound;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

final readonly class DeleteConversation
{
    public function __construct(private ConversationRepository $repository) {}

    public function __invoke(DeleteConversationData $data): void
    {
        $conversation = $this->repository->findForUser(
            ConversationId::from($data->conversationId),
            UserId::from($data->userId),
        );

        if ($conversation === null) {
            throw ConversationNotFound::withId($data->conversationId);
        }

        $conversation->delete();
        $this->repository->delete($conversation);
    }
}
