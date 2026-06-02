<?php

namespace App\Services\Frontend\UIElements\ModalNodes\Contracts;

/**
 * A presentation-oriented node of the logical screen (Two Step View).
 *
 * Nodes describe *what* to render (a form, a list), never *how*. They are
 * composable: a node may contain other nodes, so a step can hold a form or a
 * list whose items are themselves forms.
 */
interface ModalNode
{
    /**
     * Discriminator the frontend node registry uses to pick the renderer.
     */
    public function kind(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
