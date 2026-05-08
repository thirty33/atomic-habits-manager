<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse;

use Closure;
use Core\BoundedContext\Conversations\Application\Ai\AiResponseProvider;
use Core\Shared\Application\Logging\Logger;
use Throwable;

/**
 * Cuerpo extraído de `ProcessUserMessageWithAi::__invoke()` (línea 55).
 *
 * Llama al `AiResponseProvider` (port Application). El adapter
 * `LaravelAiResponseProvider` construye internamente el `AtomicIAAgent`
 * con todas sus tools (Greet, List, Create, Update, Delete, RespondToUser,
 * etc.) y hace la llamada al LLM. Este pipe NO sabe nada del SDK de
 * Laravel\Ai ni de los agents — solo invoca el port.
 *
 * Asume que `$passable->conversation` y `$passable->userMessage` ya
 * fueron cargados por los pipes anteriores.
 */
final readonly class GenerateAssistantReplyPipe
{
    public function __construct(
        private AiResponseProvider $aiProvider,
        private Logger $logger,
    ) {}

    public function handle(HandleAiResponsePassable $passable, Closure $next): mixed
    {
        $this->logger->info('[ai-pipeline] 3.generate_reply.enter calling_responder_llm', [
            'conversation_id' => $passable->conversationId,
        ]);
        $started = microtime(true);

        try {
            $passable->assistantBody = $this->aiProvider->respondTo(
                $passable->conversation,
                $passable->userMessage->body(),
            );
        } catch (Throwable $e) {
            $this->logger->error('[ai-pipeline] 3.generate_reply.threw', [
                'conversation_id' => $passable->conversationId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'duration_s' => round(microtime(true) - $started, 2),
            ]);
            throw $e;
        }

        $this->logger->info('[ai-pipeline] 3.generate_reply.ok', [
            'conversation_id' => $passable->conversationId,
            'reply_length' => strlen($passable->assistantBody?->value ?? ''),
            'duration_s' => round(microtime(true) - $started, 2),
        ]);

        return $next($passable);
    }
}
