<?php

namespace App\Services\Frontend\UIElements\ModalNodes;

use App\Services\Frontend\UIElements\Modals\Contracts\Modal;
use Illuminate\Support\Str;

/**
 * Base class for the node-based, multi-step modal (Two Step View).
 *
 * Parallel to — and independent from —
 * {@see \App\Services\Frontend\UIElements\Modals\Modal}: it neither extends nor
 * modifies it. It mirrors that class' surface (`getType()`, `generate()`) and
 * emits a `schema` discriminator so the frontend can pick the node renderer
 * without affecting the classic modals. How the two hierarchies converge is
 * decided later.
 */
class NodeModal implements Modal
{
    public const SCHEMA = 'node';

    /**
     * @param  array<int, NodeStep>  $steps
     */
    public function __construct(
        protected readonly string $type,
        protected readonly string $title,
        protected readonly array $steps,
        protected readonly ?string $maxWidth = null,
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        return array_filter([
            'id' => Str::uuid(),
            'schema' => self::SCHEMA,
            'type' => $this->type,
            'title' => $this->title,
            'steps' => array_map(fn (NodeStep $step) => $step->toArray(), $this->steps),
            'max_width' => $this->maxWidth,
        ], fn ($value) => $value !== null);
    }
}
