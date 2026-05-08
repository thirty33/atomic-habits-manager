<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Domain\HabitRepository;

/**
 * Query-side Use Case: lista los habit_ids cuyo último horizonte de
 * occurrences cae antes del threshold dado. El cron diario
 * `habits:generate-occurrences` lo usa para encolar
 * `BatchExtendOccurrencesJob` y mantener el horizonte rolling.
 *
 * El threshold es un primitivo `Y-m-d` decidido por el caller — el
 * dominio no decide cuán adelante debe vivir el horizonte; eso es
 * política de orquestación y vive en Infrastructure (Console).
 */
final readonly class ListHabitsPendingOccurrenceExtension
{
    public function __construct(
        private HabitRepository $habits,
    ) {}

    /**
     * @return list<int>
     */
    public function execute(string $thresholdYmd): array
    {
        return $this->habits->pendingExtensionIds($thresholdYmd);
    }
}
