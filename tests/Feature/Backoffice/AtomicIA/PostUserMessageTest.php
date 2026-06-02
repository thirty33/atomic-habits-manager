<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\PostUserMessage;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\DTOs\PostUserMessageData;
use Core\BoundedContext\Conversations\Domain\Events\UserMessageWasPosted;
use Core\BoundedContext\Conversations\Domain\Exceptions\ConversationNotFound;
use Core\BoundedContext\Conversations\Domain\MessageRepository;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageRole;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\MessageStatus;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\SpyDomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryAiResponseProvider;
use Tests\TestCase;

final class PostUserMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Avoid hitting the real LLM via the synchronous ScheduleAiResponse
        // listener that fires after UserMessageWasPosted.
        $this->app->instance(AiResponseProvider::class, new InMemoryAiResponseProvider);
    }

    public function test_use_case_persists_user_message_with_status_sent_and_records_event(): void
    {
        $spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $spy);

        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $spy->reset();

        $useCase = $this->app->make(PostUserMessage::class);

        $response = $useCase(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'lista mis hábitos',
        ));

        $this->assertSame(MessageRole::User->value, $response->role);
        $this->assertSame(MessageStatus::Sent->value, $response->status);
        $this->assertSame('lista mis hábitos', $response->body);

        $this->assertDatabaseHas('messages', [
            'message_id' => $response->messageId,
            'conversation_id' => $start->conversationId,
            'role' => MessageRole::User->value,
            'status' => MessageStatus::Sent->value,
            'body' => 'lista mis hábitos',
        ]);

        $events = $spy->capturedOf(UserMessageWasPosted::class);
        $this->assertCount(1, $events);
        $this->assertSame($response->messageId, $events[0]->messageId);
        $this->assertSame($start->conversationId, $events[0]->conversationId);
        $this->assertSame('lista mis hábitos', $events[0]->body);
    }

    public function test_use_case_touches_last_message_at_on_conversation(): void
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        // Move time forward so the touch is observable in the DB write.
        sleep(1);

        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'second',
        ));

        $repository = $this->app->make(\Core\BoundedContext\Conversations\Domain\ConversationRepository::class);
        $loaded = $repository->find(ConversationId::from($start->conversationId));

        $this->assertNotNull($loaded);
        $this->assertGreaterThanOrEqual(
            (int) (new \DateTimeImmutable($start->lastMessageAtIso))->getTimestamp(),
            (int) $loaded->lastMessageAt()->getTimestamp(),
        );
    }

    public function test_use_case_throws_when_conversation_belongs_to_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($owner->user_id));

        $this->expectException(ConversationNotFound::class);

        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $other->user_id,
            body: 'sneaky',
        ));
    }

    public function test_endpoint_creates_user_message_for_authenticated_owner(): void
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $payload = $this->actingAs($user)
            ->postJson("/backoffice/atomic-ia?conversation_id={$start->conversationId}", ['body' => 'hola'])
            ->assertSuccessful()
            ->json();

        $this->assertSame('hola', $payload['message']['body']);
        $this->assertSame(MessageRole::User->value, $payload['message']['role']);
        $this->assertSame(MessageStatus::Sent->value, $payload['message']['status']);

        $this->assertDatabaseHas('messages', [
            'message_id' => $payload['message']['message_id'],
            'role' => MessageRole::User->value,
            'body' => 'hola',
        ]);
    }

    public function test_endpoint_returns_404_when_conversation_belongs_to_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($owner->user_id));

        $this->actingAs($other)
            ->postJson("/backoffice/atomic-ia?conversation_id={$start->conversationId}", ['body' => 'sneaky'])
            ->assertNotFound();

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $start->conversationId,
            'body' => 'sneaky',
        ]);
    }

    public function test_repository_returns_messages_in_chronological_order_via_find_by_conversation(): void
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $useCase = $this->app->make(PostUserMessage::class);

        $first = $useCase(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'one',
        ));
        $second = $useCase(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'two',
        ));

        $messages = $this->app->make(MessageRepository::class)
            ->findByConversation(ConversationId::from($start->conversationId))
            ->items();

        $userMessageIds = array_values(array_map(
            fn ($m) => $m->messageId()->value(),
            array_filter($messages, fn ($m) => $m->role() === MessageRole::User),
        ));

        $this->assertSame([$first->messageId, $second->messageId], $userMessageIds);
    }
}
