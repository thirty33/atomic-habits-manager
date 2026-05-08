<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Outbox;

use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class EloquentOutboxRepository implements OutboxRepository
{
    public function __construct(
        private OutboxEntry $model,
        private DomainEventSerializer $serializer,
    ) {}

    /**
     * @param  list<DomainEvent>  $events
     */
    public function append(array $events): void
    {
        if ($events === []) {
            return;
        }

        $now = now();

        $rows = array_map(function (DomainEvent $e) use ($now) {
            return [
                'event_id' => $e->eventId(),
                'event_name' => $e->eventName(),
                'payload' => $this->serializer->serialize($e),
                'occurred_on' => $e->occurredOn()->format('Y-m-d H:i:s'),
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $events);

        $this->model->newQuery()->insert($rows);
    }

    /**
     * @return list<OutboxEntryDto>
     */
    public function pending(int $limit): array
    {
        $rows = $this->model->newQuery()
            ->whereNull('dispatched_at')
            ->whereNull('failed_at')
            ->orderBy('occurred_on')
            ->limit($limit)
            ->get();

        return array_values($rows->map(fn (OutboxEntry $r) => $this->toDto($r))->all());
    }

    /**
     * @return list<OutboxEntryDto>
     */
    public function listFailed(int $limit): array
    {
        $rows = $this->model->newQuery()
            ->whereNotNull('failed_at')
            ->orderBy('failed_at')
            ->limit($limit)
            ->get();

        return array_values($rows->map(fn (OutboxEntry $r) => $this->toDto($r))->all());
    }

    public function markDispatched(int $entryId): void
    {
        $this->model->newQuery()->where('id', $entryId)->update([
            'dispatched_at' => now(),
            'attempts' => DB::raw('attempts + 1'),
            'updated_at' => now(),
        ]);
    }

    public function markCompleted(int $entryId): void
    {
        $this->model->newQuery()->where('id', $entryId)->update([
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markFailed(int $entryId, string $reason): void
    {
        $this->model->newQuery()->where('id', $entryId)->update([
            'failed_at' => now(),
            'failure_reason' => substr($reason, 0, 5000),
            'updated_at' => now(),
        ]);
    }

    public function resetForRedispatch(int $entryId): void
    {
        $this->model->newQuery()->where('id', $entryId)->update([
            'dispatched_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'updated_at' => now(),
        ]);
    }

    public function findById(int $entryId): ?OutboxEntryDto
    {
        $row = $this->model->newQuery()->find($entryId);

        return $row !== null ? $this->toDto($row) : null;
    }

    private function toDto(OutboxEntry $row): OutboxEntryDto
    {
        return new OutboxEntryDto(
            id: (int) $row->getKey(),
            eventId: (string) $row->getAttribute('event_id'),
            eventName: (string) $row->getAttribute('event_name'),
            payload: (string) $row->getAttribute('payload'),
            occurredOn: $this->toDateTime($row->getAttribute('occurred_on')) ?? new DateTimeImmutable,
            attempts: (int) $row->getAttribute('attempts'),
            dispatchedAt: $this->toDateTime($row->getAttribute('dispatched_at')),
            completedAt: $this->toDateTime($row->getAttribute('completed_at')),
            failedAt: $this->toDateTime($row->getAttribute('failed_at')),
        );
    }

    private function toDateTime(mixed $raw): ?DateTimeImmutable
    {
        if ($raw === null) {
            return null;
        }

        if ($raw instanceof DateTimeImmutable) {
            return $raw;
        }

        if ($raw instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($raw);
        }

        return new DateTimeImmutable((string) $raw);
    }
}
