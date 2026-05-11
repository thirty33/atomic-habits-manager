<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\ViewModels;

use Core\BoundedContext\Conversations\Application\ConversationReader;
use Core\BoundedContext\Conversations\Application\ReadModels\ConversationSnapshot;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

/**
 * View model for the chat page (GET /backoffice/atomic-ia/json).
 *
 * Pure Application: receives the user id and the optional selected
 * conversation id, returns the JSON-ready array. The route helpers
 * (storeUrl, newConversationUrl) are passed in so this class is not
 * coupled to Laravel's `route()`.
 */
final readonly class GetAtomicIAViewModel
{
    public function __construct(private ConversationReader $reader) {}

    /**
     * @param  callable(int): string  $storeUrlBuilder  receives the conversation id, returns the store URL.
     * @return array{
     *   page_title: string,
     *   store_url: string,
     *   new_conversation_url: string,
     *   conversation: ?array<string, mixed>,
     *   conversations: list<array<string, mixed>>,
     * }
     */
    public function build(
        UserId $userId,
        ?int $selectedConversationId,
        callable $storeUrlBuilder,
        string $newConversationUrl,
    ): array {
        $current = $selectedConversationId !== null
            ? $this->reader->findForUserWithMessages($selectedConversationId, $userId)
            : $this->reader->latestForUserWithMessages($userId);

        $list = $this->reader->listForUser($userId);

        return [
            'page_title' => 'Atomic IA',
            'store_url' => $current !== null ? $storeUrlBuilder($current->conversationId) : '',
            'new_conversation_url' => $newConversationUrl,
            'conversation' => $current?->toArray(),
            'conversations' => array_map(
                static fn (ConversationSnapshot $s) => $s->toArray(),
                $list,
            ),
        ];
    }
}
