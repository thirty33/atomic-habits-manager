<?php

namespace App\Services\Frontend\UIElements\ColumnItems;

class CompoundColumn extends Column
{
    public function __construct(
        protected string $label,
        protected string $key,
        protected ?string $dataKey = null,
        protected bool $sortable = false,
        protected ?string $direction = null,
        protected ?string $sortKey = null,
    ) {}

    public function generate(): array
    {
        return array_filter([
            'component' => static::COMPONENT,
            'label' => __($this->label),
            'key' => $this->key,
            'data_key' => $this->dataKey ?? $this->key,
            'is_compound' => true,
            'sortable' => $this->sortable,
            'direction' => $this->direction,
            'sort_key' => $this->sortKey ?? $this->key,
        ], fn ($value) => $value !== null);
    }
}
