<?php

namespace App\Services\Frontend;

use App\Services\Frontend\UIElements\Buttons\Contracts\Button;

final class ButtonGenerator
{
    const CREATE_INLINE_CSS_CLASS = 'inline-flex items-center gap-2 bg-brand-700 text-paper hover:bg-brand-800 focus:ring-2 focus:outline-none focus:ring-brand-700/30 font-medium rounded-lg text-[13.5px] leading-none px-[18px] py-[11px] text-center transition-colors';

    const EXPORT_INLINE_CSS_CLASS = 'inline-flex items-center gap-2 bg-transparent text-ink-900 ring-1 ring-inset ring-line-300 hover:bg-line-100 font-medium rounded-lg text-[13.5px] leading-none px-[18px] py-[11px] text-center transition-colors';

    const SHOW_CSS_CLASS = 'inline-flex items-center gap-1.5 bg-line-100 text-ink-700 hover:bg-line-200 font-medium rounded-[7px] text-[12.5px] leading-none px-3 py-[7px] text-center transition-colors';

    const EDIT_CSS_CLASS = 'inline-flex items-center gap-1.5 bg-brand-50 text-brand-800 hover:bg-brand-100 font-medium rounded-[7px] text-[12.5px] leading-none px-3 py-[7px] text-center transition-colors';

    const DELETE_CSS_CLASS = 'inline-flex items-center gap-1.5 bg-transparent text-ink-500 ring-1 ring-inset ring-line-200 hover:text-danger-2 hover:bg-danger-2/10 hover:ring-danger-2/20 font-medium rounded-[7px] text-[12.5px] leading-none px-3 py-[7px] text-center transition-colors';

    private array $buttons = [];

    public function addButton(Button $button): self
    {
        $this->buttons[] = $button->generate();

        return $this;
    }

    public function getButtons(): array
    {
        return $this->buttons;
    }
}
