<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\EventHandlers\ScheduleAiResponse;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\LaravelAiResponseProvider;
use Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent\EloquentConversationRepository;
use Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent\EloquentMessageRepository;
use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings of the Conversations BC Domain layer to its concrete Eloquent
 * adapters, plus registration of cross-BC subscriptions for the Domain
 * Event bus, plus the AiResponseProvider adapter that drives the LLM.
 */
final class ConversationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ConversationRepository::class => EloquentConversationRepository::class,
        MessageRepository::class => EloquentMessageRepository::class,
    ];

    public function register(): void
    {
        $this->app->bind(AiResponseProvider::class, function ($app) {
            return new LaravelAiResponseProvider(
                provider: (string) config('ai.default'),
                model: (string) config('ai.model'),
                container: $app,
                auth: $app->make(AuthFactory::class),
                messages: $app->make(MessageRepository::class),
            );
        });

        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(
            \Core\BoundedContext\Conversations\Domain\Events\ConversationWasStarted::eventName(),
            \Core\BoundedContext\Conversations\Domain\Events\ConversationWasStarted::class,
        );
        $registry->register(
            \Core\BoundedContext\Conversations\Domain\Events\ConversationWasDeleted::eventName(),
            \Core\BoundedContext\Conversations\Domain\Events\ConversationWasDeleted::class,
        );
        $registry->register(
            \Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted::eventName(),
            \Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted::class,
        );
        $registry->register(
            \Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasPosted::eventName(),
            \Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasPosted::class,
        );

        $subscriptions = $this->app->make(DomainEventSubscriptions::class);
        $subscriptions->register(UserMessageWasPosted::class, ScheduleAiResponse::class);
    }
}
