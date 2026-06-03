<?php

namespace App\Services\Frontend\UIElements\ColumnItems;

use App\Services\Frontend\UIElements\ColumnItems\Contracts\ColumnItem;

class Column implements ColumnItem
{
    const COMPONENT = null;

    const KIND = null;

    public function __construct(
        protected string $label,
        protected string $key,
        protected bool $sortable = false,
        protected ?string $direction = null,
        protected ?string $trueValue = null,
        protected ?string $falseValue = null,
        protected ?string $sortKey = null,
    ) {}

    public function generate(): array
    {
        return array_filter([
            'component' => static::COMPONENT,
            'kind' => static::KIND,
            'label' => __($this->label),
            'key' => $this->key,
            'sortable' => $this->sortable,
            'direction' => $this->direction,
            'sort_key' => $this->sortKey ?? $this->key,
        ], fn ($value) => $value !== null);
    }
}
