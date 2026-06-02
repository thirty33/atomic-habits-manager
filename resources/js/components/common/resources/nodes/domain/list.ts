import { NodeKind, type ListNode, type FieldDefinition, type FormValues, type ModalModel } from "./node-schema";
import { initialValues } from "./form";

/** A seeded list item: its form values plus the identity used to tell update from create. */
export interface SeededItem {
    values: FormValues;
    identity: unknown; // existing items → their id_key value; new items → null
}

/** The field set of the list's item template (lists hold forms in this phase). */
export function itemFields(node: ListNode): FieldDefinition[] {
    return node.item_template.kind === NodeKind.Form ? node.item_template.fields : [];
}

/** Initial items: one per existing model item (carrying its identity), else min_items blanks. */
export function seedItems(node: ListNode, model: ModalModel = null): SeededItem[] {
    const fields = itemFields(node);
    const existing = node.items_key
        ? (model?.[node.items_key] as Record<string, unknown>[] | undefined)
        : undefined;

    if (existing && existing.length > 0) {
        return existing.map((item) => ({
            values: initialValues(fields, item),
            identity: node.id_key ? item[node.id_key] : null,
        }));
    }

    const count = Math.max(node.min_items ?? 1, 1);
    return Array.from({ length: count }, () => ({ values: blankItem(node), identity: null }));
}

export function blankItem(node: ListNode): FormValues {
    return initialValues(itemFields(node), null);
}

export function canAdd(node: ListNode, count: number): boolean {
    const max = node.max_items ?? null;
    return max === null || count < max;
}

export function canRemove(node: ListNode, count: number): boolean {
    return count > (node.min_items ?? 1);
}