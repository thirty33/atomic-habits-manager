<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration;

use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Agents\AtomicIAAgent;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies\HabitBulkCreateStrategy;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies\HabitBulkDeleteStrategy;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies\HabitBulkUpdateStrategy;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies\HabitCreateStrategy;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies\HabitDeleteStrategy;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies\HabitListStrategy;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies\HabitUpdateStrategy;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\BulkCreateResourceTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\BulkDeleteResourceTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\BulkUpdateResourceTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\CreateResourceTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\DeleteResourceTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\GreetTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\ListResourceTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\RespondToUserTool;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools\UpdateResourceTool;
use Illuminate\Contracts\Container\Container;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Laravel\Ai SDK adapter for the AiResponseProvider Application port.
 *
 * Builds the AtomicIAAgent + the Tool battery (resolved through the
 * container) and prompts the LLM. Each resource Tool receives the acting
 * userId by constructor, so handle() does not call auth() at all.
 */
final readonly class LaravelAiResponseProvider implements AiResponseProvider
{
    public function __construct(
        private string $provider,
        private string $model,
        private Container $container,
        private MessageRepository $messages,
    ) {}

    public function respondTo(Conversation $conversation, MessageBody $userMessage): MessageBody
    {
        $userId = $conversation->userId()->value();

        $agent = new AtomicIAAgent(
            conversation: $conversation,
            tools: $this->buildTools($userId),
            messageRepository: $this->messages,
        );

        $response = $agent->prompt(
            $userMessage->value,
            provider: $this->provider,
            model: $this->model,
        );

        return MessageBody::from($this->extractText($response));
    }

    /**
     * @return list<\Laravel\Ai\Contracts\Tool>
     */
    private function buildTools(int $userId): array
    {
        return [
            new GreetTool,
            new ListResourceTool($userId, $this->container->make(HabitListStrategy::class)),
            new CreateResourceTool($userId, $this->container->make(HabitCreateStrategy::class)),
            new UpdateResourceTool($userId, $this->container->make(HabitUpdateStrategy::class)),
            new DeleteResourceTool($userId, $this->container->make(HabitDeleteStrategy::class)),
            new BulkCreateResourceTool($userId, $this->container->make(HabitBulkCreateStrategy::class)),
            new BulkUpdateResourceTool($userId, $this->container->make(HabitBulkUpdateStrategy::class)),
            new BulkDeleteResourceTool($userId, $this->container->make(HabitBulkDeleteStrategy::class)),
            new RespondToUserTool,
        ];
    }

    private function extractText(AgentResponse $response): string
    {
        $tool = $response->toolResults->firstWhere('name', 'RespondToUserTool');

        if ($tool && ($message = $tool->arguments['message'] ?? '') !== '') {
            return $message;
        }

        $text = (string) $response;

        return $text !== '' ? $text : 'Operación completada correctamente.';
    }
}
