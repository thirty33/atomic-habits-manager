<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Jobs\ModerateMessageJob;
use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\ApproveAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\BanAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\ModerateAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\PostUserMessage;
use Core\BoundedContext\Conversations\Application\Actions\ProcessUserMessageWithAi;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\DTOs\ApproveAssistantMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\BanAssistantMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\ModerateAssistantMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\PostUserMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\ProcessUserMessageWithAiData;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasApproved;
use Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasBanned;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasBanned;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\SpyDomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\InMemoryAiModerationProvider;
use Tests\Support\InMemoryAiResponseProvider;
use Tests\TestCase;

final class ModerateAssistantMessageTest extends TestCase
{
    use RefreshDatabase;

    private InMemoryAiResponseProvider $aiProvider;

    private InMemoryAiModerationProvider $aiModerator;

    private SpyDomainEventBus $spy;

    protected function setUp(): void
    {
        parent::setUp();

        // Bind the spy first so every repository / Use Case resolved
        // afterwards gets the same instance through the container.
        $this->spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $this->spy);

        $this->aiProvider = new InMemoryAiResponseProvider;
        $this->app->instance(AiResponseProvider::class, $this->aiProvider);

        $this->aiModerator = new InMemoryAiModerationProvider(
            $this->app->make(ApproveAssistantMessage::class),
            $this->app->make(BanAssistantMessage::class),
        );
        $this->app->instance(AiModerationProvider::class, $this->aiModerator);
    }

    public function test_use_case_approves_pending_message_and_records_event(): void
    {
        [$conversationId, $assistantMessageId] = $this->seedAssistantPending();
        $this->spy->reset();

        $this->aiModerator->decision = 'approve';

        $this->app->make(ModerateAssistantMessage::class)(new ModerateAssistantMessageData(
            messageId: $assistantMessageId,
            conversationId: $conversationId,
        ));

        $this->assertCount(1, $this->aiModerator->calls);
        $this->assertDatabaseHas('messages', [
            'message_id' => $assistantMessageId,
            'status' => MessageStatus::Approved->value,
        ]);

        $events = $this->spy->capturedOf(AssistantMessageWasApproved::class);
        $this->assertCount(1, $events);
        $this->assertSame($assistantMessageId, $events[0]->messageId);
    }

    public function test_use_case_bans_pending_message_and_cascades_conversation_ban(): void
    {
        [$conversationId, $assistantMessageId] = $this->seedAssistantPending();
        $this->spy->reset();

        $this->aiModerator->decision = 'ban';
        $this->aiModerator->reason = 'prompt injection';

        $this->app->make(ModerateAssistantMessage::class)(new ModerateAssistantMessageData(
            messageId: $assistantMessageId,
            conversationId: $conversationId,
        ));

        $this->assertDatabaseHas('messages', [
            'message_id' => $assistantMessageId,
            'status' => MessageStatus::Banned->value,
        ]);
        $this->assertDatabaseHas('conversations', [
            'conversation_id' => $conversationId,
            'status' => ConversationStatus::Banned->value,
        ]);

        $banEvents = $this->spy->capturedOf(AssistantMessageWasBanned::class);
        $this->assertCount(1, $banEvents);
        $this->assertSame('prompt injection', $banEvents[0]->reason);

        $convEvents = $this->spy->capturedOf(ConversationWasBanned::class);
        $this->assertCount(1, $convEvents);
        $this->assertSame($conversationId, $convEvents[0]->conversationId);
    }

    public function test_use_case_is_idempotent_when_message_already_approved(): void
    {
        [$conversationId, $assistantMessageId] = $this->seedAssistantPending();

        $this->aiModerator->decision = 'approve';
        $useCase = $this->app->make(ModerateAssistantMessage::class);

        // First run: approves.
        $useCase(new ModerateAssistantMessageData($assistantMessageId, $conversationId));
        $this->assertCount(1, $this->aiModerator->calls);

        // Second run: status is now Approved, must no-op (no LLM call).
        $useCase(new ModerateAssistantMessageData($assistantMessageId, $conversationId));
        $this->assertCount(1, $this->aiModerator->calls, 'Moderator must not be called again');
    }

    public function test_approve_use_case_throws_when_message_not_found(): void
    {
        $this->expectException(\Core\BoundedContext\Conversations\Domain\Exceptions\MessageNotFound::class);

        $this->app->make(ApproveAssistantMessage::class)(new ApproveAssistantMessageData(messageId: 999999));
    }

    public function test_ban_use_case_throws_when_message_not_found(): void
    {
        $this->expectException(\Core\BoundedContext\Conversations\Domain\Exceptions\MessageNotFound::class);

        $this->app->make(BanAssistantMessage::class)(new BanAssistantMessageData(
            messageId: 999999,
            conversationId: 1,
        ));
    }

    public function test_command_dispatches_moderate_job_per_pending_assistant_message(): void
    {
        [$conversationIdA, $msgIdA] = $this->seedAssistantPending();
        [$conversationIdB, $msgIdB] = $this->seedAssistantPending();

        Queue::fake();

        $this->artisan('atomic-ia:moderate')->assertSuccessful();

        Queue::assertPushed(ModerateMessageJob::class, fn ($job) => $job->messageId === $msgIdA && $job->conversationId === $conversationIdA);
        Queue::assertPushed(ModerateMessageJob::class, fn ($job) => $job->messageId === $msgIdB && $job->conversationId === $conversationIdB);
        Queue::assertPushed(ModerateMessageJob::class, 2);
    }

    public function test_moderate_message_job_invokes_use_case(): void
    {
        [$conversationId, $assistantMessageId] = $this->seedAssistantPending();
        $this->aiModerator->decision = 'approve';

        $job = new ModerateMessageJob($assistantMessageId, $conversationId);
        $job->handle($this->app->make(ModerateAssistantMessage::class));

        $this->assertCount(1, $this->aiModerator->calls);
        $this->assertDatabaseHas('messages', [
            'message_id' => $assistantMessageId,
            'status' => MessageStatus::Approved->value,
        ]);
    }

    /**
     * @return array{0: int, 1: int} [conversationId, assistantMessageId]
     */
    private function seedAssistantPending(): array
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'lista mis hábitos',
        ));

        $this->aiProvider->cannedBody = 'Aquí tienes tus hábitos.';
        $this->app->make(ProcessUserMessageWithAi::class)(new ProcessUserMessageWithAiData(
            conversationId: $start->conversationId,
        ));

        $latest = $this->app->make(MessageRepository::class)
            ->latestForConversation(\Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId::from($start->conversationId));

        return [$start->conversationId, $latest->messageId()->value()];
    }
}
