<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\AtomicIA;

use App\Models\User;
use Core\BoundedContext\Conversations\Application\Actions\PostUserMessage;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\BoundedContext\Conversations\Application\ConversationReader;
use Core\BoundedContext\Conversations\Application\DTOs\PostUserMessageData;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryAiResponseProvider;
use Tests\TestCase;

final class LoadChatPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Avoid LLM calls fired by the synchronous ScheduleAiResponse listener.
        $this->app->instance(AiResponseProvider::class, new InMemoryAiResponseProvider);
    }

    public function test_reader_returns_user_conversations_summary_ordered_by_last_message_desc(): void
    {
        $user = User::factory()->create();

        $startA = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        sleep(1);
        $startB = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $list = $this->app->make(ConversationReader::class)->listForUser(UserId::from($user->user_id));

        $this->assertCount(2, $list);
        $this->assertSame($startB->conversationId, $list[0]->conversationId);
        $this->assertSame($startA->conversationId, $list[1]->conversationId);
        $this->assertSame([], $list[0]->messages, 'Listing view does not include messages');
    }

    public function test_reader_returns_conversation_with_messages_in_chronological_order_for_user(): void
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'first',
        ));
        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'second',
        ));

        $snapshot = $this->app->make(ConversationReader::class)
            ->findForUserWithMessages($start->conversationId, UserId::from($user->user_id));

        $this->assertNotNull($snapshot);
        $this->assertNotEmpty($snapshot->messages);
        $bodies = array_map(fn ($m) => $m->body, $snapshot->messages);
        $this->assertContains('first', $bodies);
        $this->assertContains('second', $bodies);
    }

    public function test_reader_returns_null_for_other_user_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($owner->user_id));

        $reader = $this->app->make(ConversationReader::class);

        $this->assertNull(
            $reader->findForUserWithMessages($start->conversationId, UserId::from($other->user_id)),
        );
    }

    public function test_endpoint_returns_payload_with_selected_conversation_when_query_param_set(): void
    {
        $user = User::factory()->create();
        $start = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $this->app->make(PostUserMessage::class)(new PostUserMessageData(
            conversationId: $start->conversationId,
            userId: $user->user_id,
            body: 'hola',
        ));

        $payload = $this->actingAs($user)
            ->getJson(route('backoffice.atomic-ia.json', ['conversation_id' => $start->conversationId]))
            ->assertSuccessful()
            ->json();

        $this->assertSame('Atomic IA', $payload['page_title']);
        $this->assertNotEmpty($payload['store_url']);
        $this->assertSame($start->conversationId, $payload['conversation']['conversation_id']);
        $this->assertNotEmpty($payload['conversation']['messages']);
    }

    public function test_endpoint_picks_latest_conversation_when_no_query_param(): void
    {
        $user = User::factory()->create();
        $startA = $this->app->make(StartConversation::class)(UserId::from($user->user_id));
        sleep(1);
        $startB = $this->app->make(StartConversation::class)(UserId::from($user->user_id));

        $payload = $this->actingAs($user)
            ->getJson(route('backoffice.atomic-ia.json'))
            ->assertSuccessful()
            ->json();

        $this->assertSame($startB->conversationId, $payload['conversation']['conversation_id']);
        $this->assertCount(2, $payload['conversations']);
    }

    public function test_endpoint_returns_empty_payload_when_user_has_no_conversations(): void
    {
        $user = User::factory()->create();

        $payload = $this->actingAs($user)
            ->getJson(route('backoffice.atomic-ia.json'))
            ->assertSuccessful()
            ->json();

        $this->assertNull($payload['conversation']);
        $this->assertSame('', $payload['store_url']);
        $this->assertSame([], $payload['conversations']);
    }
}
