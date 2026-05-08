<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Outbox;

use Core\Shared\Domain\Events\DomainEvent;

/**
 * Port for the events outbox. Lives in Infrastructure (not Domain) because
 * the outbox is a delivery mechanism, not a domain concept.
 */
interface OutboxRepository
{
    /**
     * Persist events into the outbox. Designed to be invoked inside the
     * Repository transaction of the aggregate, guaranteeing atomicity
     * with the state changes.
     *
     * @param  list<DomainEvent>  $events
     */
    public function append(array $events): void;

    /**
     * Returns up to $limit entries with `dispatched_at IS NULL`, oldest
     * first. Does NOT mark — only reads.
     *
     * @return list<OutboxEntryDto>
     */
    public function pending(int $limit): array;

    /**
     * Returns up to $limit entries with `failed_at IS NOT NULL`, oldest
     * first. Used by `events:relay --redispatch-failed` to reopen entries
     * that exhausted retries after a deterministic listener bug, once
     * fixed.
     *
     * @return list<OutboxEntryDto>
     */
    public function listFailed(int $limit): array;

    /**
     * Marks an entry as dispatched to the queue.
     */
    public function markDispatched(int $entryId): void;

    /**
     * Marks an entry as completed (listener finished OK).
     */
    public function markCompleted(int $entryId): void;

    /**
     * Records a definitive failure for the entry.
     */
    public function markFailed(int $entryId, string $reason): void;

    /**
     * Idempotency: reopens an entry for redispatch (admin / replay).
     */
    public function resetForRedispatch(int $entryId): void;

    /**
     * Reads an entry by id (used by HandleDomainEventJob).
     */
    public function findById(int $entryId): ?OutboxEntryDto;
}
