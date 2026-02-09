<?php

namespace App\Services\Frontend\UIElements\FormFields;

class TextField implements Contracts\Field
{
    use Concerns\HasGridLayout;
    use Concerns\HasMaxLength;
    use Concerns\HasRequiredIndicator;

    const COMPONENT = 'AppTextInputField';

    const CSS_LABEL_CLASS = 'block mb-1 text-sm font-medium text-gray-900 dark:text-white';

    const CSS_FIELD_CLASS = 'bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:placeholder-gray-400 dark:text-white dark:bg-gray-700';

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
                'cssLabelClass' => self::CSS_LABEL_CLASS,
                'cssFieldClass' => self::CSS_FIELD_CLASS,
                ...$this->maxLengthProps(),
                ...$this->requiredIndicatorProps(),
            ],
        ], fn ($v) => $v !== null);
    }
}
