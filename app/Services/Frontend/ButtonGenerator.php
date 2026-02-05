<?php

namespace App\Services\Frontend;

use App\Services\Frontend\UIElements\Buttons\Contracts\Button;

final class ButtonGenerator
{
    const CREATE_INLINE_CSS_CLASS = 'inline-flex items-center gap-1 text-white bg-btn-primary hover:bg-btn-primary-hover focus:ring-4 focus:outline-none focus:ring-btn-primary/30 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors';

    const EXPORT_INLINE_CSS_CLASS = 'inline-flex items-center gap-1 text-white bg-btn-success hover:bg-btn-success-hover focus:ring-4 focus:outline-none focus:ring-btn-success/30 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors';

    const SHOW_CSS_CLASS = 'text-white bg-btn-secondary hover:bg-btn-secondary-hover focus:ring-4 focus:outline-none focus:ring-btn-secondary/30 font-medium rounded-lg text-xs px-3 py-1.5 text-center transition-colors';

    const EDIT_CSS_CLASS = 'text-white bg-btn-info hover:bg-btn-info-hover focus:ring-4 focus:outline-none focus:ring-btn-info/30 font-medium rounded-lg text-xs px-3 py-1.5 text-center transition-colors';

    const DELETE_CSS_CLASS = 'text-white bg-btn-danger hover:bg-btn-danger-hover focus:ring-4 focus:outline-none focus:ring-btn-danger/30 font-medium rounded-lg text-xs px-3 py-1.5 text-center transition-colors';

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
