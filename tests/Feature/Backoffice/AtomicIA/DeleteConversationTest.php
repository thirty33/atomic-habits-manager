<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Models\Conversation as ConversationModel;
use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\DeleteConversation;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\DTOs\DeleteConversationData;
use Core\BoundedContext\Conversations\Domain\Events\ConversationWasDeleted;
use Core\BoundedContext\Conversations\Domain\Exceptions\ConversationNotFound;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\SpyDomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeleteConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_use_case_soft_deletes_and_records_event(): void
    {
        $spy = new SpyDomainEventBus;
        $this->app->instance(DomainEventBus::class, $spy);

        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        $spy->reset();

        $delete = $this->app->make(DeleteConversation::class);

        $delete(new DeleteConversationData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
        ));

        $this->assertDatabaseMissing('conversations', [
            'conversation_id' => $start->conversationId,
        ]);

        $events = $spy->capturedOf(ConversationWasDeleted::class);
        $this->assertCount(1, $events);
        $this->assertSame($start->conversationId, $events[0]->conversationId);
        $this->assertSame($user->user_id, $events[0]->userId);
    }

    public function test_use_case_throws_when_conversation_belongs_to_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($owner->user_id));

        $this->expectException(ConversationNotFound::class);

        $this->app->make(DeleteConversation::class)(new DeleteConversationData(
            conversationId: $start->conversationId,
            userId: $other->user_id,
        ));
    }

    public function test_endpoint_returns_404_when_conversation_belongs_to_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($owner->user_id));

        $this->actingAs($other)
            ->deleteJson("/backoffice/atomic-ia/conversations/{$start->conversationId}")
            ->assertNotFound();

        $this->assertNotNull(ConversationModel::query()->find($start->conversationId));
    }

    public function test_endpoint_deletes_owned_conversation(): void
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $this->actingAs($user)
            ->deleteJson("/backoffice/atomic-ia/conversations/{$start->conversationId}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('conversations', [
            'conversation_id' => $start->conversationId,
        ]);
    }
}
