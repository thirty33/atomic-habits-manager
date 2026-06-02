<?php

namespace App\Services\Frontend\UIElements\ModalNodes;

use App\Services\Frontend\UIElements\ModalNodes\Contracts\ModalNode;

/**
 * Container node: a repeatable list whose items are themselves nodes.
 *
 * `itemTemplate` is any `ModalNode` (typically a {@see FormNode}), which makes
 * the structure recursive — a list of forms (e.g. the collapsible accordion of
 * habit schedules). The list does not own an HTTP action; the owning
 * {@see NodeStep} submits the collected items under `payloadKey`.
 */
final class ListNode implements ModalNode
{
    public const KIND = 'list';

    /**
     * @param  ModalNode  $itemTemplate  Blueprint cloned for every new item.
     * @param  array<int, string>  $summaryFields  Field names shown in the collapsed item summary.
     * @param  string|null  $itemsKey  Model field holding the existing items to seed on read (e.g. `schedules`).
     * @param  string|null  $payloadKey  Key under which the items array is sent in the step submit (e.g. `schedules`).
     * @param  string|null  $idKey  Item identity key used to tell update from create (e.g. `habit_schedule_id`).
     * @param  string|null  $syncActionKey  Model key holding the baked sync ActionForm (e.g. `schedules_sync_action`).
     */
    public function __construct(
        protected readonly ModalNode $itemTemplate,
        protected readonly ?string $addLabel = null,
        protected readonly int $minItems = 1,
        protected readonly ?int $maxItems = null,
        protected readonly array $summaryFields = [],
        protected readonly bool $collapsible = true,
        protected readonly ?string $itemsKey = null,
        protected readonly ?string $payloadKey = null,
        protected readonly ?string $idKey = null,
        protected readonly ?string $syncActionKey = null,
    ) {}

    public function kind(): string
    {
        return self::KIND;
    }

    public function toArray(): array
    {
        return array_filter([
            'kind' => self::KIND,
            'item_template' => $this->itemTemplate->toArray(),
            'add_label' => $this->addLabel,
            'min_items' => $this->minItems,
            'max_items' => $this->maxItems,
            'summary_fields' => $this->summaryFields,
            'collapsible' => $this->collapsible,
            'items_key' => $this->itemsKey,
            'payload_key' => $this->payloadKey,
            'id_key' => $this->idKey,
            'sync_action_key' => $this->syncActionKey,
        ], fn ($value) => $value !== null);
    }
}
