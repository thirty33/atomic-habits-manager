<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration;

use App\Ai\Strategies\HabitBulkCreateStrategy;
use App\Ai\Strategies\HabitBulkDeleteStrategy;
use App\Ai\Strategies\HabitBulkUpdateStrategy;
use App\Ai\Strategies\HabitCreateStrategy;
use App\Ai\Strategies\HabitDeleteStrategy;
use App\Ai\Strategies\HabitListStrategy;
use App\Ai\Strategies\HabitUpdateStrategy;
use App\Ai\Tools\BulkCreateResourceTool;
use App\Ai\Tools\BulkDeleteResourceTool;
use App\Ai\Tools\BulkUpdateResourceTool;
use App\Ai\Tools\CreateResourceTool;
use App\Ai\Tools\DeleteResourceTool;
use App\Ai\Tools\GreetTool;
use App\Ai\Tools\ListResourceTool;
use App\Ai\Tools\RespondToUserTool;
use App\Ai\Tools\UpdateResourceTool;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageBody;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Agents\AtomicIAAgent;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Container\Container;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Laravel\Ai SDK adapter for the AiResponseProvider Application port.
 *
 * Builds the AtomicIAAgent + the Tool battery (resolved through the
 * container so the legacy strategies still work during the staged
 * migration), prompts the LLM, and extracts the response body.
 *
 * Concession to be cleaned up in flow 10: legacy Tools call
 * auth()->id() inside their handle() to identify the acting user. We
 * temporarily set up auth here using loginUsingId so that pathway keeps
 * working. Flow 10 refactors Tools to take int $userId by constructor;
 * once that lands, this loginUsingId call disappears.
 */
final readonly class LaravelAiResponseProvider implements AiResponseProvider
{
    public function __construct(
        private string $provider,
        private string $model,
        private Container $container,
        private AuthFactory $auth,
        private MessageRepository $messages,
    ) {}

    public function respondTo(Conversation $conversation, MessageBody $userMessage): MessageBody
    {
        $this->auth->guard('web')->loginUsingId($conversation->userId()->value());

        $agent = new AtomicIAAgent(
            conversation: $conversation,
            tools: $this->buildTools(),
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
    private function buildTools(): array
    {
        return [
            new GreetTool,
            new ListResourceTool($this->container->make(HabitListStrategy::class)),
            new CreateResourceTool($this->container->make(HabitCreateStrategy::class)),
            new UpdateResourceTool($this->container->make(HabitUpdateStrategy::class)),
            new DeleteResourceTool($this->container->make(HabitDeleteStrategy::class)),
            new BulkCreateResourceTool($this->container->make(HabitBulkCreateStrategy::class)),
            new BulkUpdateResourceTool($this->container->make(HabitBulkUpdateStrategy::class)),
            new BulkDeleteResourceTool($this->container->make(HabitBulkDeleteStrategy::class)),
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
