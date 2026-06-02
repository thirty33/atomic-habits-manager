/**
 * Domain model of the node modal (the "logical screen" the ViewModel emits —
 * Two Step View). Pure types + discriminators. Mirrors the PHP constants
 * FormNode::KIND, ListNode::KIND, NodeModal::SCHEMA. No framework, no UI concern
 * (the kind→component mapping lives in the adapters layer, not here).
 */

export const ModalSchema = { Node: "node" } as const;
export type ModalSchema = (typeof ModalSchema)[keyof typeof ModalSchema];

export const NodeKind = { Form: "form", List: "list" } as const;
export type NodeKind = (typeof NodeKind)[keyof typeof NodeKind];

/** A field definition as produced by PHP `Field::generate()`. */
export interface FieldDefinition {
    uuid: string;
    component: string;
    props: { name: string; defaultValue?: unknown; [key: string]: unknown };
    visible_when?: Record<string, unknown>;
    col_span?: number;
    md_col_span?: number;
    xl_col_span?: number;
    row_span?: number;
}

export interface FormNode {
    kind: typeof NodeKind.Form;
    fields: FieldDefinition[];
}

export interface ListNode {
    kind: typeof NodeKind.List;
    item_template: ModalNode;
    add_label?: string;
    min_items: number;
    max_items?: number | null;
    summary_fields?: string[];
    collapsible?: boolean;
    items_key?: string;
    payload_key?: string;
    id_key?: string;           // item identity key (e.g. 'habit_schedule_id')
    sync_action_key?: string;  // model key holding the baked sync action (e.g. 'schedules_sync_action')
}

export type ModalNode = FormNode | ListNode;

/** An HTTP action the backend baked into the screen (url + method + headers). */
export interface ActionRequest {
    url: string;
    method: string;
    headers?: Record<string, string>;
}

export interface NodeStep {
    step: number;
    title: string;
    subtitle?: string;
    submit_text: string;
    is_optional?: boolean;
    skip_text?: string;
    action?: ActionRequest; // baked action (used by createNode step 1; empty in editNode)
    content: ModalNode;
}

export interface NodeModalData {
    id?: string;
    schema: typeof ModalSchema.Node;
    type: string;
    title: string;
    max_width?: string;
    steps: NodeStep[];
}

/** Plain value objects (no framework). */
export type FormValues = Record<string, unknown>;
export type ModalModel = Record<string, unknown> | null;
