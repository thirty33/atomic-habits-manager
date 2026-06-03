// TS types mirroring the JSON the backend already sends (the "logical screen" of a cell,
// Two-Step View stage 1, produced server-side by the ViewModel + Resource). This is NOT a
// second ViewModel: no logic, no derivation here — only the shape. New column kinds add a
// variant. The column-level `kind` (config discriminator) can map to any vm kind here
// (e.g. a `date` column produces a `text` vm).
export interface ScheduleLine {
    id: string | number | null;
    recurrenceLabel: string;
    timeRange: string;
    detail: string | null;
}

export interface ActionItem {
    label: string;
    class: string;
    event: string;
}

export type CellViewModel =
    | { kind: "text"; value: string | null }
    | { kind: "boolean"; value: boolean; trueLabel: string; falseLabel: string }
    | { kind: "schedules"; items: ScheduleLine[]; emptyText: string }
    | { kind: "actions"; actions: ActionItem[] };