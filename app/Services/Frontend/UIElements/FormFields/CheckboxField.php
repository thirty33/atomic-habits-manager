<?php

namespace App\Services\Frontend\UIElements\FormFields;

class CheckboxField implements Contracts\Field
{
    use Concerns\HasGridLayout;
    use Concerns\HasRequiredIndicator;

    const COMPONENT = 'AppCheckboxField';

    const CSS_FIELD_CLASS = 'w-4 h-4 rounded border-line-300 text-brand-700 focus:ring-brand-700/30 focus:ring-offset-0';

    const CSS_LABEL_CLASS = 'ms-2 text-[13.5px] font-medium text-ink-700';

    public function __construct(
        protected string $name,
        protected string $label,
        protected string $placeholder = '',
        protected bool $defaultValue = false,
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
                'cssFieldClass' => self::CSS_FIELD_CLASS,
                'cssLabelClass' => self::CSS_LABEL_CLASS,
                'defaultValue' => $this->defaultValue,
                ...$this->requiredIndicatorProps(),
            ],
        ], fn ($v) => $v !== null);
    }
}
