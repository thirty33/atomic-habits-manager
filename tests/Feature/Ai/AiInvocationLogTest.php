<?php

namespace Tests\Feature\Ai;

use App\Ai\Agents\AtomicIAAgent;
use App\Ai\Agents\ModeratorAgent;
use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Models\AiInvocationLog;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\Data\Usage;
use Tests\TestCase;

class AiInvocationLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->conversation = Conversation::create([
            'user_id' => $this->user->user_id,
            'title' => 'Test',
            'status' => ConversationStatus::Active,
        ]);
    }

    public function test_it_logs_agent_invocation_without_tool_calls(): void
    {
        AtomicIAAgent::fake(['Hola! Soy Atomic IA.']);

        $agent = new AtomicIAAgent($this->conversation);
        $agent->prompt('hola', provider: 'deepseek', model: 'deepseek-chat');

        $this->assertDatabaseCount('ai_invocation_logs', 1);
        $this->assertDatabaseHas('ai_invocation_logs', [
            'user_id' => $this->user->user_id,
            'agent' => 'AtomicIAAgent',
            'response' => 'Hola! Soy Atomic IA.',
            'tool_calls' => null,
        ]);
    }

    public function test_it_logs_compact_tool_calls(): void
    {
        $agent = new AtomicIAAgent($this->conversation);

        $invocationId = (string) Str::uuid();
        $agentResponse = new AgentResponse($invocationId, 'Listo, hábito eliminado.', new Usage(100, 50), new Meta);

        $agentResponse->toolResults = collect([
            new ToolResult(
                id: 'tc_1',
                name: 'delete_resource',
                arguments: ['resource' => 'habit', 'id' => 3],
                result: 'Hábito eliminado correctamente.',
            ),
        ]);

        $provider = $this->createMock(TextProvider::class);
        $agentPrompt = new AgentPrompt($agent, 'elimina el hábito 3', collect(), $provider, 'deepseek-chat');

        event(new AgentPrompted($invocationId, $agentPrompt, $agentResponse));

        $log = AiInvocationLog::first();
        $this->assertNotNull($log);
        $this->assertSame(
            'delete_resource(resource="habit", id=3) → "Hábito eliminado correctamente."',
            $log->tool_calls
        );
        $this->assertSame($this->user->user_id, $log->user_id);
        $this->assertSame(100, $log->prompt_tokens);
        $this->assertSame(50, $log->completion_tokens);
    }

    public function test_it_logs_moderator_agent_invocation(): void
    {
        ModeratorAgent::fake(['approved']);

        $message = \App\Models\Message::withoutEvents(fn () => $this->conversation->messages()->create([
            'role' => MessageRole::User,
            'type' => 'text',
            'body' => 'hola',
            'status' => MessageStatus::Sent,
        ]));
        $message->setRelation('conversation', $this->conversation);

        $agent = new ModeratorAgent($message);
        $agent->prompt('moderate this', provider: 'deepseek', model: 'deepseek-chat');

        $this->assertDatabaseCount('ai_invocation_logs', 1);
        $this->assertDatabaseHas('ai_invocation_logs', [
            'user_id' => $this->user->user_id,
            'agent' => 'ModeratorAgent',
        ]);
    }

    public function test_it_stores_null_response_when_text_is_empty(): void
    {
        AtomicIAAgent::fake(['']);

        $agent = new AtomicIAAgent($this->conversation);
        $agent->prompt('test', provider: 'deepseek', model: 'deepseek-chat');

        $this->assertDatabaseHas('ai_invocation_logs', [
            'agent' => 'AtomicIAAgent',
            'response' => null,
        ]);
    }

    public function test_it_stores_null_user_id_for_agents_without_has_user_id(): void
    {
        $invocationId = (string) Str::uuid();

        // Anonymous agent (no HasUserId)
        $agent = \Laravel\Ai\agent('You are a test assistant.');
        $agentResponse = new AgentResponse($invocationId, 'ok', new Usage, new Meta);

        $provider = $this->createMock(TextProvider::class);
        $agentPrompt = new AgentPrompt($agent, 'hello', collect(), $provider, 'deepseek-chat');

        event(new AgentPrompted($invocationId, $agentPrompt, $agentResponse));

        $this->assertDatabaseHas('ai_invocation_logs', [
            'user_id' => null,
            'response' => 'ok',
        ]);
    }
}
