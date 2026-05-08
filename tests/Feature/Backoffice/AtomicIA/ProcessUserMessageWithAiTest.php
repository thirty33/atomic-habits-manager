<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\PostUserMessage;
use Core\BoundedContext\Conversations\Application\Actions\ProcessUserMessageWithAi;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\DTOs\PostUserMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\ProcessUserMessageWithAiData;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\InMemorySyncDomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\SpyDomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryAiResponseProvider;
use Tests\TestCase;

final class ProcessUserMessageWithAiTest extends TestCase
{
    use RefreshDatabase;

    private InMemoryAiResponseProvider $aiProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiProvider = new InMemoryAiResponseProvider;
        $this->app->instance(AiResponseProvider::class, $this->aiProvider);
    }

    public function test_use_case_persists_pending_assistant_message_and_emits_event(): void
    {
        // Spy bus first so the synchronous listener does NOT fire when
        // PostUserMessage publishes UserMessageWasPosted. We exercise the
        // explicit Use Case invocation in isolation.
        $spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $spy);

        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'lista mis hábitos',
        ));
        $spy->reset();

        $this->aiProvider->cannedBody = 'Aquí tienes tus hábitos.';

        $this->app->make(ProcessUserMessageWithAi::class)(new ProcessUserMessageWithAiData(
            conversationId: $start->conversationId,
        ));

        $this->assertCount(1, $this->aiProvider->calls);
        $this->assertSame('lista mis hábitos', $this->aiProvider->calls[0]['user_message']);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $start->conversationId,
            'role' => MessageRole::Assistant->value,
            'status' => MessageStatus::Pending->value,
            'body' => 'Aquí tienes tus hábitos.',
        ]);

        $events = $spy->capturedOf(AssistantMessageWasPosted::class);
        $this->assertCount(1, $events);
        $this->assertSame($start->conversationId, $events[0]->conversationId);
        $this->assertSame('Aquí tienes tus hábitos.', $events[0]->body);
    }

    public function test_use_case_is_idempotent_when_latest_message_is_already_assistant(): void
    {
        $spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $spy);

        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'hola',
        ));

        $useCase = $this->app->make(ProcessUserMessageWithAi::class);

        // First invocation: writes the assistant reply.
        $useCase(new ProcessUserMessageWithAiData(conversationId: $start->conversationId));
        $this->assertCount(1, $this->aiProvider->calls);

        // Second invocation: latest message is now assistant, must no-op.
        $useCase(new ProcessUserMessageWithAiData(conversationId: $start->conversationId));
        $this->assertCount(1, $this->aiProvider->calls, 'AI provider must not be called again');

        $this->assertSame(
            1,
            $this->countAssistantMessages($start->conversationId),
            'Only one assistant message should exist',
        );
    }

    public function test_use_case_no_ops_when_conversation_is_banned(): void
    {
        $spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $spy);

        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'hola',
        ));

        // Force the conversation to Banned out of band (simulating flow 07).
        \DB::table('conversations')
            ->where('conversation_id', $start->conversationId)
            ->update(['status' => ConversationStatus::Banned->value]);

        $useCase = $this->app->make(ProcessUserMessageWithAi::class);
        $this->aiProvider->calls = [];

        $useCase(new ProcessUserMessageWithAiData(conversationId: $start->conversationId));

        $this->assertSame([], $this->aiProvider->calls);
    }

    public function test_use_case_no_ops_when_conversation_does_not_exist(): void
    {
        $useCase = $this->app->make(ProcessUserMessageWithAi::class);

        $useCase(new ProcessUserMessageWithAiData(conversationId: 999999));

        $this->assertSame([], $this->aiProvider->calls);
    }

    public function test_listener_path_runs_use_case_when_user_message_event_fires(): void
    {
        // Use the real InMemorySync bus so the listener fires synchronously.
        $bus = $this->app->make(InMemorySyncDomainEventBus::class);
        $this->app->instance(DomainEventBus::class, $bus);

        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'hola',
        ));

        // The listener (ScheduleAiResponse) is wired to UserMessageWasPosted.
        $this->assertCount(1, $this->aiProvider->calls);
        $this->assertSame('hola', $this->aiProvider->calls[0]['user_message']);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $start->conversationId,
            'role' => MessageRole::Assistant->value,
            'status' => MessageStatus::Pending->value,
        ]);
    }

    public function test_process_conversation_job_invokes_use_case(): void
    {
        // Spy bus from the start so the listener does NOT fire — we
        // exercise the cron-only safety-net path explicitly.
        $spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $spy);

        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'cron',
        ));

        // Listener did not fire (spy bus). Now the cron-only Job path:
        $job = new \App\Jobs\ProcessConversationJob($start->conversationId);
        $job->handle($this->app->make(ProcessUserMessageWithAi::class));

        $this->assertCount(1, $this->aiProvider->calls);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $start->conversationId,
            'role' => MessageRole::Assistant->value,
            'status' => MessageStatus::Pending->value,
        ]);
    }

    private function countAssistantMessages(int $conversationId): int
    {
        return $this->app->make(MessageRepository::class)
            ->findByConversation(ConversationId::from($conversationId))
            ->count() - 1; // minus the user message
    }
}
