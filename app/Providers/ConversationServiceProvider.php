<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Conversations\Application\Actions\ApproveAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\BanAssistantMessage;
use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\Broadcasting\ConversationBroadcaster;
use Core\BoundedContext\Conversations\Application\ConversationReader;
use Core\BoundedContext\Conversations\Application\EventHandlers\BroadcastApprovedMessage;
use Core\BoundedContext\Conversations\Application\EventHandlers\BroadcastConversationStatus;
use Core\BoundedContext\Conversations\Application\EventHandlers\HandleAiResponseListener;
use Core\BoundedContext\Conversations\Application\EventHandlers\ModerateAssistantMessageOnPost;
use Core\BoundedContext\Conversations\Application\EventHandlers\PostFallbackOnBan;
use Core\BoundedContext\Conversations\Application\EventHandlers\ScheduleAiResponse;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasApproved;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasBanned;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasBanned;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasDeleted;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasStarted;
use Core\BoundedContext\Conversations\Domain\Events\FallbackMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\LaravelAiModerationProvider;
use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\LaravelAiResponseProvider;
use Core\BoundedContext\Conversations\Infrastructure\Broadcasting\LaravelEchoConversationBroadcaster;
use Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent\EloquentConversationReader;
use Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent\EloquentConversationRepository;
use Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent\EloquentMessageRepository;
use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings of the Conversations BC Domain layer to its concrete Eloquent
 * adapters, plus AI providers, broadcasting, domain-event class registry,
 * and listener subscriptions.
 */
final class ConversationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ConversationRepository::class => EloquentConversationRepository::class,
        ConversationReader::class => EloquentConversationReader::class,
        MessageRepository::class => EloquentMessageRepository::class,
        ConversationBroadcaster::class => LaravelEchoConversationBroadcaster::class,
    ];

    public function register(): void
    {
        $this->app->bind(AiResponseProvider::class, function ($app) {
            return new LaravelAiResponseProvider(
                provider: (string) config('ai.default'),
                model: (string) config('ai.model'),
                container: $app,
                messages: $app->make(MessageRepository::class),
            );
        });

        $this->app->bind(AiModerationProvider::class, function ($app) {
            return new LaravelAiModerationProvider(
                provider: (string) config('ai.default'),
                model: (string) config('ai.model'),
                approve: $app->make(ApproveAssistantMessage::class),
                ban: $app->make(BanAssistantMessage::class),
            );
        });

        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(ConversationWasStarted::eventName(), ConversationWasStarted::class);
        $registry->register(ConversationWasDeleted::eventName(), ConversationWasDeleted::class);
        $registry->register(ConversationWasBanned::eventName(), ConversationWasBanned::class);
        $registry->register(UserMessageWasPosted::eventName(), UserMessageWasPosted::class);
        $registry->register(AssistantMessageWasPosted::eventName(), AssistantMessageWasPosted::class);
        $registry->register(AssistantMessageWasApproved::eventName(), AssistantMessageWasApproved::class);
        $registry->register(AssistantMessageWasBanned::eventName(), AssistantMessageWasBanned::class);
        $registry->register(FallbackMessageWasPosted::eventName(), FallbackMessageWasPosted::class);

        $subscriptions = $this->app->make(DomainEventSubscriptions::class);

        // ── Pipeline collapse (estudio-unificacion-pipeline-ia.md) ───────
        // Suscripción nueva: un único listener async que invoca el
        // `HandleAiResponseAction` (basado en `Illuminate\Pipeline`) con
        // los 7 pipes en serie. Reduce los 3 hops por outbox del flujo
        // anterior a 1, sin cambiar funcionalidad.
        $subscriptions->register(UserMessageWasPosted::class, HandleAiResponseListener::class);

        // ── Suscripciones legacy (deshabilitadas durante validación) ─────
        // Mantenemos los archivos de los listeners en disco para rollback
        // rápido si la regresión browser detecta algún problema con el
        // pipeline. Cuando esté validado, estas líneas se borran y los
        // archivos se renombran a `.php.delete`.
        // $subscriptions->register(UserMessageWasPosted::class, ScheduleAiResponse::class);
        // $subscriptions->register(AssistantMessageWasPosted::class, ModerateAssistantMessageOnPost::class);
        // $subscriptions->register(AssistantMessageWasBanned::class, PostFallbackOnBan::class);
        // $subscriptions->register(AssistantMessageWasApproved::class, BroadcastApprovedMessage::class);
        // $subscriptions->register(FallbackMessageWasPosted::class, BroadcastApprovedMessage::class);

        // BroadcastConversationStatus se mantiene — es independiente del
        // path de respuesta de la IA y solo broadcastea cambios de status
        // de la conversación (banned por moderación o eliminada).
        $subscriptions->register(ConversationWasBanned::class, BroadcastConversationStatus::class);
    }
}
