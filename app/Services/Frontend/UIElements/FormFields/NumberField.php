<?php

namespace App\Services\Frontend\UIElements\FormFields;

class NumberField implements Contracts\Field
{
    use Concerns\HasGridLayout;
    use Concerns\HasRequiredIndicator;

    const COMPONENT = 'AppNumberField';

    const CSS_LABEL_CLASS = 'block mb-2 text-sm font-medium text-gray-900 dark:text-white';

    const CSS_FIELD_CLASS = 'bg-gray-50 p-1.6 mt-3 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500';

    protected ?array $visibleWhen = null;

    public function __construct(
        protected string $name,
        protected string $label,
        protected ?int $min = null,
        protected ?int $max = null,
        protected ?int $defaultValue = null,
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
                'cssFieldClass' => self::CSS_FIELD_CLASS,
                'cssLabelClass' => self::CSS_LABEL_CLASS,
                'min' => $this->min,
                'max' => $this->max,
                'defaultValue' => $this->defaultValue,
                ...$this->requiredIndicatorProps(),
            ],
        ], fn ($v) => $v !== null);
    }
}
