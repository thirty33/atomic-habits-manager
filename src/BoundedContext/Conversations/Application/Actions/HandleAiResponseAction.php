<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\Actions;

use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\BroadcastFinalMessagePipe;
use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\GenerateAssistantReplyPipe;
use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\HandleAiResponsePassable;
use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\LoadAndValidateConversationPipe;
use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\LoadLatestUserMessagePipe;
use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\ModerateAssistantMessagePipe;
use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\PersistPendingAssistantMessagePipe;
use Core\BoundedContext\Conversations\Application\Actions\HandleAiResponse\PostFallbackIfBannedPipe;
use Core\BoundedContext\Conversations\Application\DTOs\HandleAiResponseData;
use Core\Shared\Application\Logging\Logger;
use Core\Shared\Application\Persistence\TransactionManager;
use Core\Shared\Application\Pipeline\PipelineRunner;
use Throwable;

/**
 * Único Use Case asíncrono que orquesta el ciclo completo de respuesta
 * de la IA. Reemplaza los Use Cases `ProcessUserMessageWithAi` y
 * `ModerateAssistantMessage` y absorbe los listeners thin
 * `ScheduleAiResponse`, `ModerateAssistantMessageOnPost`,
 * `PostFallbackOnBan` y `BroadcastApprovedMessage` en pipes serializados.
 *
 * Ventajas respecto al diseño anterior (mantiene la misma funcionalidad):
 *  - 1 hop por outbox en el path crítico (UserMessageWasPosted) en
 *    lugar de 3. Reduce p99 de ~44s a ~18s.
 *  - 1 listener async en lugar de 4.
 *  - Una sola transacción DB envuelve el ciclo, garantizando que si la
 *    moderación o el broadcast tira excepción, no quede el assistant
 *    message persistido en estado inconsistente.
 *
 * Idempotencia preservada: cada pipe tiene su propia guarda por estado
 * (conversación Active, último mensaje del user, status Pending del
 * assistant message). Si el bucket Heavy Job reintenta tras fallo, el
 * pipeline corre de nuevo y solo escribe lo que falte.
 */
final readonly class HandleAiResponseAction
{
    /**
     * @param  list<class-string>  $pipes
     */
    public function __construct(
        private PipelineRunner $pipeline,
        private TransactionManager $transactions,
        private Logger $logger,
        private array $pipes = [
            LoadAndValidateConversationPipe::class,
            LoadLatestUserMessagePipe::class,
            GenerateAssistantReplyPipe::class,
            PersistPendingAssistantMessagePipe::class,
            ModerateAssistantMessagePipe::class,
            PostFallbackIfBannedPipe::class,
            BroadcastFinalMessagePipe::class,
        ],
    ) {}

    public function __invoke(HandleAiResponseData $data): void
    {
        $this->logger->info('[ai-pipeline] action.enter', ['conversation_id' => $data->conversationId]);
        try {
            $this->transactions->execute(function () use ($data): void {
                $this->logger->info('[ai-pipeline] tx.begin', ['conversation_id' => $data->conversationId]);
                $passable = new HandleAiResponsePassable(
                    conversationId: $data->conversationId,
                );

                $this->pipeline->run($passable, $this->pipes);
                $this->logger->info('[ai-pipeline] tx.about_to_commit', ['conversation_id' => $data->conversationId]);
            });
            $this->logger->info('[ai-pipeline] action.exit committed', ['conversation_id' => $data->conversationId]);
        } catch (Throwable $e) {
            $this->logger->error('[ai-pipeline] action.threw', [
                'conversation_id' => $data->conversationId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'at' => $e->getFile().':'.$e->getLine(),
            ]);
            throw $e;
        }
    }
}
