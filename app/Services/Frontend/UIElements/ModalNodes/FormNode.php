<?php

namespace App\Services\Frontend\UIElements\ModalNodes;

use App\Services\Frontend\UIElements\ModalNodes\Contracts\ModalNode;

/**
 * Leaf node: a single form made of field definitions.
 *
 * The fields are the very same arrays produced by the existing `Field`
 * classes (`Field::generate()`), so this node only groups them under a `form`
 * discriminator without touching the field system.
 */
final class FormNode implements ModalNode
{
    public const KIND = 'form';

    /**
     * @param  array<int, array<string, mixed>>  $fields  Field definitions produced by `Field::generate()`.
     */
    public function __construct(
        protected readonly array $fields,
    ) {}

    public function kind(): string
    {
        return self::KIND;
    }

    public function toArray(): array
    {
        return [
            'kind' => self::KIND,
            'fields' => $this->fields,
        ];
    }
}
