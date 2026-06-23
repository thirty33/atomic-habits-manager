// Reports domain — progress & per-status aggregation over entries (pure).

import { STATE_ORDER } from './status.js';

export function computeProgress(entries) {
    const total = entries.length;
    const reported = entries.filter((e) => e.status !== 'pending').length;
    const completed = entries.filter((e) => e.status === 'completed').length;
    const percent = total > 0 ? Math.round((reported / total) * 100) : 0;
    return { total, reported, completed, percent, isComplete: total > 0 && reported === total };
}

/** Count of entries per status, in display order. */
export function statusCounts(entries) {
    const counts = Object.fromEntries(STATE_ORDER.map((s) => [s, 0]));
    for (const e of entries) {
        counts[e.status] = (counts[e.status] ?? 0) + 1;
    }
    return counts;
}

/** Adherence bar color (token) by percent. */
export function adherenceColor(percent) {
    if (percent >= 100) return 'rgb(var(--color-success-2))';
    if (percent >= 75) return 'rgb(var(--color-brand-700))';
    if (percent >= 50) return 'rgb(var(--color-warning))';
    return 'rgb(var(--color-danger-2))';
}
