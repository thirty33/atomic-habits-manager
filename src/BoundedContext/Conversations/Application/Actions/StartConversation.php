<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\Responses\ConversationResponse;
use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

final readonly class StartConversation
{
    public function __construct(private ConversationRepository $repository) {}

    public function __invoke(UserId $userId): ConversationResponse
    {
        $conversation = Conversation::start($userId);

        $this->repository->save($conversation);

        return ConversationResponse::fromAggregate($conversation);
    }
}
