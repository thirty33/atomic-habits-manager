<?php

namespace App\Services\Frontend\UIElements\Modals;

use App\Services\Frontend\UIElements\ActionForm;

class ModalStep
{
    public function __construct(
        public readonly int $step,
        public readonly string $title,
        public readonly array $formFields,
        public readonly ActionForm $action,
        public readonly string $textSubmitButton = 'Siguiente',
        public readonly bool $isOptional = false,
        public readonly ?string $textSkipButton = null,
    ) {}

    public function toArray(): array
    {
        return [
            'step' => $this->step,
            'title' => $this->title,
            'form_fields' => $this->formFields,
            'action' => $this->action->toArray(),
            'text_submit_button' => $this->textSubmitButton,
            'is_optional' => $this->isOptional,
            'text_skip_button' => $this->textSkipButton,
        ];
    }
}