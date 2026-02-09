<?php

namespace App\Services\Frontend\UIElements\FormFields\Concerns;

trait HasGridLayout
{
    protected ?int $colSpan = null;

    protected ?int $mdColSpan = null;

    protected ?int $xlColSpan = null;

    protected ?int $rowSpan = null;

    public function colSpan(int $cols): static
    {
        $this->colSpan = $cols;

        return $this;
    }

    public function mdColSpan(int $cols): static
    {
        $this->mdColSpan = $cols;

        return $this;
    }

    public function xlColSpan(int $cols): static
    {
        $this->xlColSpan = $cols;

        return $this;
    }

    public function rowSpan(int $rows): static
    {
        $this->rowSpan = $rows;

        return $this;
    }

    protected function gridLayoutData(): array
    {
        return array_filter([
            'col_span' => $this->colSpan,
            'md_col_span' => $this->mdColSpan,
            'xl_col_span' => $this->xlColSpan,
            'row_span' => $this->rowSpan,
        ], fn ($v) => $v !== null);
    }
}
