<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\DTOs\BanConversationData;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Exceptions\ConversationNotFound;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;

/**
 * Use Case for banning a conversation.
 *
 * Introduced in flow 06 because BanAssistantMessage requires it (a
 * banned assistant message cascades to a banned conversation, by
 * design). Flow 07 extends it with the broadcaster + fallback message.
 */
final readonly class BanConversation
{
    public function __construct(private ConversationRepository $conversations) {}

    public function __invoke(BanConversationData $data): void
    {
        $conversation = $this->conversations->find(ConversationId::from($data->conversationId));

        if ($conversation === null) {
            throw ConversationNotFound::withId($data->conversationId);
        }

        $conversation->ban($data->reason);
        $this->conversations->save($conversation);
    }
}
