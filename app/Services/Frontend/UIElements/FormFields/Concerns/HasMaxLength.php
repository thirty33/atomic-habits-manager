<?php

namespace App\Services\Frontend\UIElements\FormFields\Concerns;

trait HasMaxLength
{
    protected ?int $maxLength = null;

    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    protected function maxLengthProps(): array
    {
        return array_filter([
            'maxLength' => $this->maxLength,
        ], fn ($v) => $v !== null);
    }
}
