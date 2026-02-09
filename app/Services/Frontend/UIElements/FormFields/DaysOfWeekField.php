<?php

namespace App\Services\Frontend\UIElements\FormFields;

class DaysOfWeekField implements Contracts\Field
{
    use Concerns\HasGridLayout;
    use Concerns\HasRequiredIndicator;

    const COMPONENT = 'AppDaysOfWeekField';

    const CSS_LABEL_CLASS = 'block mb-2 text-sm font-medium text-gray-900 dark:text-white';

    protected ?array $visibleWhen = null;

    public function __construct(
        protected string $name,
        protected string $label,
        protected ?array $defaultValue = null,
    ) {}

    public function visibleWhen(array $condition): static
    {
        $this->visibleWhen = $condition;

        return $this;
    }

    public function generate(): array
    {
        return array_filter([
            'uuid' => \Str::uuid(),
            'component' => self::COMPONENT,
            ...$this->gridLayoutData(),
            'visible_when' => $this->visibleWhen,
            'props' => [
                'name' => $this->name,
                'label' => __($this->label),
                'cssLabelClass' => self::CSS_LABEL_CLASS,
                'defaultValue' => $this->defaultValue,
                ...$this->requiredIndicatorProps(),
            ],
        ], fn ($v) => $v !== null);
    }
}
