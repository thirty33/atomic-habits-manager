<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Events\Outbox;

use Illuminate\Database\Eloquent\Model;

/**
 * Internal Eloquent model for the events_outbox table. Lives next to the
 * adapter that uses it; not exposed outside this Outbox namespace.
 */
final class OutboxEntry extends Model
{
    protected $table = 'domain_event_outbox';

    public $timestamps = true;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_on' => 'datetime',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
