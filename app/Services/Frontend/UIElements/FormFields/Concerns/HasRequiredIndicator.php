<?php

namespace App\Services\Frontend\UIElements\FormFields\Concerns;

trait HasRequiredIndicator
{
    protected bool $isRequired = false;

    public function required(bool $required = true): static
    {
        $this->isRequired = $required;

        return $this;
    }

    protected function requiredIndicatorProps(): array
    {
        return array_filter([
            'isRequired' => $this->isRequired ?: null,
        ], fn ($v) => $v !== null);
    }
}
