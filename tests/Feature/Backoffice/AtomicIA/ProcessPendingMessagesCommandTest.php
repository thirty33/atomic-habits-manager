<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Jobs\ProcessConversationJob;
use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\PostUserMessage;
use Core\BoundedContext\Conversations\Application\Actions\ProcessUserMessageWithAi;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\DTOs\PostUserMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\ProcessUserMessageWithAiData;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\SpyDomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\InMemoryAiResponseProvider;
use Tests\TestCase;

final class ProcessPendingMessagesCommandTest extends TestCase
{
    use RefreshDatabase;

    private InMemoryAiResponseProvider $aiProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiProvider = new InMemoryAiResponseProvider;
        $this->app->instance(AiResponseProvider::class, $this->aiProvider);

        // Spy bus so PostUserMessage doesn't trigger ScheduleAiResponse —
        // we want to observe what idsAwaitingAiResponse + the cron command
        // produce on a conversation that DID NOT yet get an assistant reply.
        $this->app->instance(DomainEventBus::class, new SpyDomainEventBus);
    }

    public function test_repository_returns_ids_of_conversations_with_latest_message_from_user(): void
    {
        $user = User::factory()->create();

        $waitingStart = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $waitingStart->conversationId,
            userId: $user->user_id,
            body: 'sin respuesta aún',
        ));

        $repliedStart = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $repliedStart->conversationId,
            userId: $user->user_id,
            body: 'ya respondida',
        ));
        $this->app->make(ProcessUserMessageWithAi::class)(new ProcessUserMessageWithAiData(
            conversationId: $repliedStart->conversationId,
        ));

        $ids = $this->app->make(ConversationRepository::class)->idsAwaitingAiResponse();

        $this->assertContains($waitingStart->conversationId, $ids);
        $this->assertNotContains($repliedStart->conversationId, $ids);
    }

    public function test_command_dispatches_one_process_conversation_job_per_waiting_conversation(): void
    {
        $user = User::factory()->create();

        $startA = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $startA->conversationId,
            userId: $user->user_id,
            body: 'a',
        ));

        $startB = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $startB->conversationId,
            userId: $user->user_id,
            body: 'b',
        ));

        Queue::fake();

        $this->artisan('atomic-ia:process')->assertSuccessful();

        Queue::assertPushed(ProcessConversationJob::class, fn ($job) => $job->conversationId === $startA->conversationId);
        Queue::assertPushed(ProcessConversationJob::class, fn ($job) => $job->conversationId === $startB->conversationId);
        Queue::assertPushed(ProcessConversationJob::class, 2);
    }
}
