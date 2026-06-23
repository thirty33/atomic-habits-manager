// Reports domain — entry construction, occurrence reconciliation and ordering (pure).

export function createEmptyEntry() {
    return {
        daily_report_entry_id: null,
        habit_occurrence_id: null,
        habit_id: null,
        custom_activity: null,
        start_time: '',
        end_time: '',
        status: 'pending',
        notes: null,
        is_free_activity: true,
        habit: null,
    };
}

export function occurrenceToEntry(occurrence) {
    return {
        daily_report_entry_id: null,
        habit_occurrence_id: occurrence.habit_occurrence_id,
        habit_id: occurrence.habit_id,
        custom_activity: null,
        start_time: occurrence.start_time,
        end_time: occurrence.end_time,
        status: 'pending',
        notes: null,
        is_free_activity: false,
        habit: occurrence.habit ?? null,
    };
}

/** Flag whether a (server) entry is a free activity (no habit/occurrence link). */
export function markServerEntry(entry) {
    return { ...entry, is_free_activity: !entry.habit_id && !entry.habit_occurrence_id };
}

const byStartTime = (a, b) => (a.start_time || '').localeCompare(b.start_time || '');

/**
 * Merge persisted entries with the day's occurrences: keep all existing entries,
 * append a pending entry for every occurrence not yet reported, sorted by start time.
 */
export function mergeEntries(serverEntries, occurrences) {
    const existing = (serverEntries ?? []).map(markServerEntry);
    const used = new Set(existing.map((e) => e.habit_occurrence_id).filter(Boolean));
    const fromOccurrences = (occurrences ?? [])
        .filter((occ) => !used.has(occ.habit_occurrence_id))
        .map(occurrenceToEntry);

    return [...existing, ...fromOccurrences].sort(byStartTime);
}

/** Display title for an entry: habit name, custom activity, or a placeholder. */
export function entryTitle(entry) {
    return entry.habit?.name ?? entry.custom_activity ?? 'Actividad sin nombre';
}

/** Minutes between start and end (handles same-day only); null if incomplete. */
export function entryDurationLabel(entry) {
    if (!entry.start_time || !entry.end_time) {
        return null;
    }
    const [sh, sm] = entry.start_time.split(':').map(Number);
    const [eh, em] = entry.end_time.split(':').map(Number);
    let mins = eh * 60 + em - (sh * 60 + sm);
    if (mins < 0) {
        mins += 1440;
    }
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h > 0 ? `${h}h${m > 0 ? ` ${m}m` : ''}` : `${m}m`;
}
