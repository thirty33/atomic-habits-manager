<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\ApproveAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\BanAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\PostUserMessage;
use Core\BoundedContext\Conversations\Application\Actions\ProcessUserMessageWithAi;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\Broadcasting\ConversationBroadcaster;
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
use Tests\Support\InMemoryAiModerationProvider;
use Tests\Support\InMemoryAiResponseProvider;
use Tests\TestCase;

final class ProcessUserMessageWithAiTest extends TestCase
{
    use RefreshDatabase;

    private InMemoryAiResponseProvider $aiProvider;

    private InMemoryAiModerationProvider $aiModerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiProvider = new InMemoryAiResponseProvider;
        $this->app->instance(AiResponseProvider::class, $this->aiProvider);

        // The collapsed pipeline (HandleAiResponseListener) runs moderation
        // inline, so the moderator double must always be wired — otherwise
        // any test that exercises the listener path will explode at the
        // ModerateAssistantMessagePipe with a real LLM call.
        $this->aiModerator = new InMemoryAiModerationProvider(
            $this->app->make(ApproveAssistantMessage::class),
            $this->app->make(BanAssistantMessage::class),
        );
        $this->app->instance(AiModerationProvider::class, $this->aiModerator);

        // Stub the broadcaster: the pipeline's BroadcastFinalMessagePipe
        // calls it after moderation. A null implementation keeps the test
        // from reaching the real Echo binding.
        $this->app->instance(ConversationBroadcaster::class, new class implements ConversationBroadcaster
        {
            public function statusChanged(int $conversationId, string $status): void {}

            public function messageReady(int $conversationId, array $messagePayload): void {}
        });
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

    public function test_listener_path_runs_pipeline_when_user_message_event_fires(): void
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

        // The listener (HandleAiResponseListener) is wired to
        // UserMessageWasPosted and runs the full collapsed pipeline:
        // generate → persist pending → moderate (default decision is
        // 'approve') → broadcast. The assistant message ends up Approved,
        // not Pending — the moderation pipe runs inline in the same
        // transaction.
        $this->assertCount(1, $this->aiProvider->calls);
        $this->assertSame('hola', $this->aiProvider->calls[0]['user_message']);
        $this->assertCount(1, $this->aiModerator->calls);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $start->conversationId,
            'role' => MessageRole::Assistant->value,
            'status' => MessageStatus::Approved->value,
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
