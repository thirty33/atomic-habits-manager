<?php

namespace App\Services\Frontend\UIElements\ModalNodes;

use App\Services\Frontend\UIElements\ActionForm;
use App\Services\Frontend\UIElements\ModalNodes\Contracts\ModalNode;

/**
 * A single step of a {@see NodeModal}.
 *
 * Flow concerns live here (the submit/skip chrome and the HTTP action), while
 * the step's body is delegated to a polymorphic {@see ModalNode} content — a
 * form or a list. Mirrors the existing
 * {@see \App\Services\Frontend\UIElements\Modals\ModalStep} so both hierarchies
 * can be merged later.
 */
final class NodeStep
{
    public function __construct(
        public readonly int $step,
        public readonly string $title,
        public readonly ActionForm $action,
        public readonly ModalNode $content,
        public readonly ?string $subtitle = null,
        public readonly string $submitText = 'Siguiente',
        public readonly bool $isOptional = false,
        public readonly ?string $skipText = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'step' => $this->step,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'action' => $this->action->toArray(),
            'content' => $this->content->toArray(),
            'submit_text' => $this->submitText,
            'is_optional' => $this->isOptional,
            'skip_text' => $this->skipText,
        ], fn ($value) => $value !== null);
    }
}
