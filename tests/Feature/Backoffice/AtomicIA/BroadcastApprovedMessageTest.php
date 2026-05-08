<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\ApproveAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\BanAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\ModerateAssistantMessage;
use Core\BoundedContext\Conversations\Application\Actions\PostUserMessage;
use Core\BoundedContext\Conversations\Application\Actions\ProcessUserMessageWithAi;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\Ai\AiModerationProvider;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\Broadcasting\ConversationBroadcaster;
use Core\BoundedContext\Conversations\Application\DTOs\ModerateAssistantMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\PostUserMessageData;
use Core\BoundedContext\Conversations\Application\DTOs\ProcessUserMessageWithAiData;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\InMemorySyncDomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryAiModerationProvider;
use Tests\Support\InMemoryAiResponseProvider;
use Tests\TestCase;

final class BroadcastApprovedMessageTest extends TestCase
{
    use RefreshDatabase;

    private InMemoryAiResponseProvider $aiProvider;

    private InMemoryAiModerationProvider $aiModerator;

    /** @var array<int, array{type: string, args: array<int, mixed>}> */
    private array $broadcastCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        $bus = $this->app->make(InMemorySyncDomainEventBus::class);
        $this->app->instance(DomainEventBus::class, $bus);

        $this->aiProvider = new InMemoryAiResponseProvider;
        $this->app->instance(AiResponseProvider::class, $this->aiProvider);

        $this->aiModerator = new InMemoryAiModerationProvider(
            $this->app->make(ApproveAssistantMessage::class),
            $this->app->make(BanAssistantMessage::class),
        );
        $this->app->instance(AiModerationProvider::class, $this->aiModerator);

        $this->broadcastCalls = [];
        $self = $this;
        $this->app->instance(ConversationBroadcaster::class, new class($self) implements ConversationBroadcaster
        {
            public function __construct(private readonly BroadcastApprovedMessageTest $test) {}

            public function statusChanged(int $conversationId, string $status): void
            {
                $this->test->captureBroadcast('statusChanged', [$conversationId, $status]);
            }

            public function messageReady(int $conversationId, array $messagePayload): void
            {
                $this->test->captureBroadcast('messageReady', [$conversationId, $messagePayload]);
            }
        });
    }

    public function captureBroadcast(string $type, array $args): void
    {
        $this->broadcastCalls[] = ['type' => $type, 'args' => $args];
    }

    public function test_listener_broadcasts_message_when_assistant_message_is_approved(): void
    {
        $assistantMessageId = $this->seedAssistantPending();

        $this->aiModerator->decision = 'approve';

        $this->app->make(ModerateAssistantMessage::class)(new ModerateAssistantMessageData(
            messageId: $assistantMessageId,
            conversationId: $this->lastConversationId,
        ));

        $messageReadyCalls = $this->callsOf('messageReady');
        $this->assertCount(1, $messageReadyCalls);
        $this->assertSame($this->lastConversationId, $messageReadyCalls[0]['args'][0]);
        $this->assertSame($assistantMessageId, $messageReadyCalls[0]['args'][1]['message_id']);
        $this->assertSame(MessageRole::Assistant->value, $messageReadyCalls[0]['args'][1]['role']);
        $this->assertSame(MessageStatus::Approved->value, $messageReadyCalls[0]['args'][1]['status']);
    }

    public function test_listener_broadcasts_fallback_message_when_fallback_is_posted(): void
    {
        $assistantMessageId = $this->seedAssistantPending();

        $this->aiModerator->decision = 'ban';
        $this->aiModerator->reason = 'system prompt leak';

        $this->app->make(ModerateAssistantMessage::class)(new ModerateAssistantMessageData(
            messageId: $assistantMessageId,
            conversationId: $this->lastConversationId,
        ));

        // We expect TWO broadcaster calls: status='banned' and the fallback messageReady.
        $statusCalls = $this->callsOf('statusChanged');
        $messageCalls = $this->callsOf('messageReady');

        $this->assertCount(1, $statusCalls);
        $this->assertSame('banned', $statusCalls[0]['args'][1]);

        $this->assertCount(1, $messageCalls);
        $this->assertTrue($messageCalls[0]['args'][1]['metadata']['fallback'] ?? false);
        $this->assertSame(MessageStatus::Approved->value, $messageCalls[0]['args'][1]['status']);
    }

    public function test_listener_no_ops_when_message_no_longer_exists(): void
    {
        $bus = $this->app->make(InMemorySyncDomainEventBus::class);
        $this->app->instance(DomainEventBus::class, $bus);

        $listener = $this->app->make(\Core\BoundedContext\Conversations\Application\EventHandlers\BroadcastApprovedMessage::class);

        $event = new \Core\BoundedContext\Conversations\Domain\Events\AssistantMessageWasApproved(
            messageId: 999_999,
            conversationId: 1,
            reason: null,
        );

        $listener($event);

        $this->assertSame([], $this->callsOf('messageReady'));
    }

    /**
     * @return list<array{type: string, args: array<int, mixed>}>
     */
    private function callsOf(string $type): array
    {
        return array_values(array_filter(
            $this->broadcastCalls,
            fn ($call) => $call['type'] === $type,
        ));
    }

    private int $lastConversationId = 0;

    private function seedAssistantPending(): int
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $this->lastConversationId = $start->conversationId;

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
            ->latestForConversation(ConversationId::from($start->conversationId));

        // Reset captured calls — we want to observe ONLY what happens
        // during the moderation step under test.
        $this->broadcastCalls = [];

        return $latest->messageId()->value();
    }
}
