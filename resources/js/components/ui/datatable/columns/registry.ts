import type { Component } from "vue";
import type { CellViewModel } from "./contracts/cell";
import AppCellTextTable from "./ui/table/AppCellText.vue";
import AppCellTextCard from "./ui/card/AppCellText.vue";
import AppCellDateTable from "./ui/table/AppCellDate.vue";
import AppCellBooleanTable from "./ui/table/AppCellBoolean.vue";
import AppCellBooleanCard from "./ui/card/AppCellBoolean.vue";
import AppCellSchedulesTable from "./ui/table/AppCellSchedules.vue";
import AppCellSchedulesCard from "./ui/card/AppCellSchedules.vue";
import AppCellActionsTable from "./ui/table/AppCellActions.vue";
import AppCellActionsCard from "./ui/card/AppCellActions.vue";

export type Viewport = "table" | "card";

export interface ColumnConfig {
    kind?: string;
    key: string;
    data_key?: string;
    true_value?: string;
    false_value?: string;
    label?: string;
    actions?: Array<{ label: string; class: string; event: string }>;
}

interface RegistryEntry {
    // Stage-1 projection: maps the already-normalized row into the renderer's view-model.
    // Pure translation (no derivation — the backend Resource owns the logical screen).
    present: (row: Record<string, unknown>, column: ColumnConfig) => CellViewModel;
    ui: Partial<Record<Viewport, Component>>;
}

const textPresent = (row: Record<string, unknown>, column: ColumnConfig): CellViewModel => ({
    kind: "text",
    value: (row[column.key] ?? null) as string | null,
});

// Plugin (PoEAA p.499) + Separated Interface: kind → { projection, renderers per viewport }.
// A missing kind/renderer means the host falls back to the legacy path (incremental migration).
const REGISTRY: Record<string, RegistryEntry> = {
    text: { present: textPresent, ui: { table: AppCellTextTable, card: AppCellTextCard } },
    date: { present: textPresent, ui: { table: AppCellDateTable, card: AppCellTextCard } },
    boolean: {
        present: (row, column) => ({
            kind: "boolean",
            value: Boolean(row[column.key]),
            trueLabel: column.true_value ?? "",
            falseLabel: column.false_value ?? "",
        }),
        ui: { table: AppCellBooleanTable, card: AppCellBooleanCard },
    },
    schedules: {
        present: (row, column) => ({
            kind: "schedules",
            items: ((row[column.data_key ?? column.key] as Record<string, any>[]) ?? []).map((s) => ({
                id: s.habit_schedule_id ?? null,
                recurrenceLabel: s.recurrence_label ?? s.recurrence_type_label,
                timeRange: s.time_range,
                detail: s.detail ?? null,
            })),
            emptyText: "Sin programación",
        }),
        ui: { table: AppCellSchedulesTable, card: AppCellSchedulesCard },
    },
    actions: {
        present: (_row, column) => ({ kind: "actions", actions: column.actions ?? [] }),
        ui: { table: AppCellActionsTable, card: AppCellActionsCard },
    },
};

export function resolveRenderer(kind: string | undefined, viewport: Viewport): Component | null {
    if (!kind) {
        return null;
    }

    return REGISTRY[kind]?.ui[viewport] ?? null;
}

export function project(
    kind: string | undefined,
    row: Record<string, unknown>,
    column: ColumnConfig,
): CellViewModel | null {
    if (!kind) {
        return null;
    }

    return REGISTRY[kind]?.present(row, column) ?? null;
}