<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Models\Conversation as ConversationModel;
use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Domain\Conversation;
use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasStarted;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationId;
use Core\BoundedContext\Conversations\Domain\ValueObjects\Concretes\ConversationStatus;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\SpyDomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StartConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_use_case_persists_active_conversation_with_default_title_and_records_started_event(): void
    {
        $spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $spy);

        $user = User::factory()->create();

        $useCase = $this->app->make(StartConversation::class);

        $response = $useCase(UserId::from($user->user_id));

        $this->assertSame($user->user_id, $response->userId);
        $this->assertSame(ConversationStatus::Active->value, $response->status);

        $this->assertDatabaseHas('conversations', [
            'conversation_id' => $response->conversationId,
            'user_id' => $user->user_id,
            'status' => ConversationStatus::Active->value,
        ]);

        $events = $spy->capturedOf(ConversationWasStarted::class);
        $this->assertCount(1, $events);
        /** @var ConversationWasStarted $event */
        $event = $events[0];
        $this->assertSame($response->conversationId, $event->conversationId);
        $this->assertSame($user->user_id, $event->userId);
    }

    public function test_repository_loads_back_persisted_conversation_via_find_for_user(): void
    {
        $user = User::factory()->create();
        $useCase = $this->app->make(StartConversation::class);

        $response = $useCase(UserId::from($user->user_id));

        $repository = $this->app->make(ConversationRepository::class);
        $loaded = $repository->findForUser(
            ConversationId::from($response->conversationId),
            UserId::from($user->user_id),
        );

        $this->assertInstanceOf(Conversation::class, $loaded);
        $this->assertSame($response->conversationId, $loaded->conversationId()?->value());
        $this->assertSame(ConversationStatus::Active, $loaded->status());
    }

    public function test_find_for_user_returns_null_for_other_users_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->app->make(StartConversation::class)(UserId::from($owner->user_id));

        $repository = $this->app->make(ConversationRepository::class);

        $this->assertNull(
            $repository->findForUser(
                ConversationId::from($response->conversationId),
                UserId::from($other->user_id),
            ),
        );
    }

    public function test_endpoint_creates_conversation_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $payload = $this->actingAs($user)
            ->postJson(route('backoffice.atomic-ia.new-conversation'))
            ->assertSuccessful()
            ->json();

        $this->assertArrayHasKey('conversation', $payload);
        $this->assertArrayHasKey('store_url', $payload);
        $this->assertSame(ConversationStatus::Active->value, $payload['conversation']['status']);

        $row = ConversationModel::query()->find($payload['conversation']['conversation_id']);
        $this->assertNotNull($row);
        $this->assertSame($user->user_id, (int) $row->user_id);
    }
}
