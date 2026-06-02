<?php

namespace App\Services\Frontend\UIElements\FormFields;

class DateField implements Contracts\Field
{
    use Concerns\HasGridLayout;
    use Concerns\HasRequiredIndicator;

    const COMPONENT = 'AppDateField';

    const CSS_LABEL_CLASS = 'block mb-1.5 text-[13px] font-medium text-ink-700';

    const CSS_FIELD_CLASS = 'block w-full bg-card border-0 px-3.5 py-[11px] rounded-lg text-[14.5px] text-ink-900 placeholder:text-ink-400 shadow-[inset_0_0_0_1px_rgb(var(--color-line-200))] focus:shadow-[inset_0_0_0_1.5px_rgb(var(--color-brand-700))] focus:outline-none focus:ring-0 transition-shadow disabled:opacity-60 disabled:cursor-not-allowed';

    protected ?array $visibleWhen = null;

    public function __construct(
        protected string $name,
        protected string $label,
        protected ?string $defaultValue = null,
        protected ?string $max = null,
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
                'defaultValue' => $this->defaultValue,
                'max' => $this->max,
                ...$this->requiredIndicatorProps(),
            ],
        ], fn ($v) => $v !== null);
    }
}
