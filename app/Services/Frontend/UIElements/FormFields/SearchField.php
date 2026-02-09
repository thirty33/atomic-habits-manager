<?php

namespace App\Services\Frontend\UIElements\FormFields;

class SearchField implements Contracts\Field
{
    use Concerns\HasGridLayout;
    use Concerns\HasMaxLength;
    use Concerns\HasRequiredIndicator;

    const COMPONENT = 'AppSearchInputField';

    const CSS_FIELD_CLASS = 'block w-full p-2 mt-8 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500';

    public function __construct(
        protected string $name,
        protected string $label,
        protected string $placeholder = '',
    ) {}

    public function generate(): array
    {
        return array_filter([
            'uuid' => \Str::uuid(),
            'component' => self::COMPONENT,
            ...$this->gridLayoutData(),
            'props' => [
                'name' => $this->name,
                'label' => __($this->label),
                'placeholder' => __($this->placeholder),
                'cssFieldClass' => self::CSS_FIELD_CLASS,
                ...$this->maxLengthProps(),
                ...$this->requiredIndicatorProps(),
            ],
        ], fn ($v) => $v !== null);
    }
}
