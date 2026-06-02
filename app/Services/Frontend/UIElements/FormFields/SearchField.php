<?php

namespace App\Services\Frontend\UIElements\FormFields;

class SearchField implements Contracts\Field
{
    use Concerns\HasGridLayout;
    use Concerns\HasMaxLength;
    use Concerns\HasRequiredIndicator;

    const COMPONENT = 'AppSearchInputField';

    const CSS_FIELD_CLASS = 'block w-full bg-card border-0 ps-10 pe-3.5 py-[11px] rounded-lg text-[14.5px] text-ink-900 placeholder:text-ink-400 shadow-[inset_0_0_0_1px_rgb(var(--color-line-200))] focus:shadow-[inset_0_0_0_1.5px_rgb(var(--color-brand-700))] focus:outline-none focus:ring-0 transition-shadow disabled:opacity-60 disabled:cursor-not-allowed';

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
