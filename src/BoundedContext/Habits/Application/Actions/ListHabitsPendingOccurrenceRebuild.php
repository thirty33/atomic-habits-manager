<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Domain\HabitRepository;

/**
 * Query-side Use Case: lista los habit_ids con `needs_occurrence_rebuild`
 * activo. Lo consume el cron `habits:generate-occurrences` para encolar
 * `BatchGenerateOccurrencesJob` por chunks.
 *
 * No retorna agregados ni snapshots — solo IDs primitivos. La lectura es
 * trivial; el Use Case existe para mantener el contrato "command → Action
 * → Repository" sin que la capa Infrastructure (Console) hable
 * directamente con Domain.
 */
final readonly class ListHabitsPendingOccurrenceRebuild
{
    public function __construct(
        private HabitRepository $habits,
    ) {}

    /**
     * @return list<int>
     */
    public function execute(): array
    {
        return $this->habits->pendingRebuildIds();
    }
}
