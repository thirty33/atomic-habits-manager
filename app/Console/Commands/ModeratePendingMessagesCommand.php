<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Core\BoundedContext\Conversations\Application\Actions\ListPendingAssistantMessages;
use Core\BoundedContext\Conversations\Application\Actions\ModerateAssistantMessage;
use Core\BoundedContext\Conversations\Application\DTOs\ModerateAssistantMessageData;
use Illuminate\Console\Command;
use Throwable;

/**
 * Cron safety-net de moderación: el camino normal corre por el listener
 * `ModerateAssistantMessageOnPost` (suscrito a `AssistantMessageWasPosted`,
 * bucket Heavy). Si ese listener falla — error transitorio, deploy con
 * outbox congelado, fallback en el provider — algún mensaje del asistente
 * queda en estado Pending y nunca llega al usuario.
 *
 * Este command corre `everyMinute` (ver routes/console.php), localiza
 * los Pending y reaplica `ModerateAssistantMessage` sync. Sync porque
 * el cron tiene tiempo y, si el path async está roto, llamar otro Job
 * tendría el mismo riesgo.
 *
 * Cero acceso directo a repositorios — todo pasa por Use Cases.
 */
final class ModeratePendingMessagesCommand extends Command
{
    protected $signature = 'atomic-ia:moderate';

    protected $description = 'Modera (sync) los mensajes del asistente que siguen en estado Pending.';

    public function handle(
        ListPendingAssistantMessages $listPending,
        ModerateAssistantMessage $moderate,
    ): int {
        $refs = $listPending->execute();

        $moderated = 0;
        $errors = 0;
        foreach ($refs as $ref) {
            try {
                ($moderate)(new ModerateAssistantMessageData(
                    messageId: $ref->messageId,
                    conversationId: $ref->conversationId,
                ));
                $moderated++;
            } catch (Throwable $e) {
                $errors++;
                $this->components->warn(
                    "Moderación falló para message {$ref->messageId}: {$e->getMessage()}"
                );
            }
        }

        $total = count($refs);
        $this->components->info(
            "Moderados: {$moderated} de {$total} (errores: {$errors})."
        );

        return self::SUCCESS;
    }
}
