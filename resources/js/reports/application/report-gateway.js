// Reports application — gateway PORT for the editor.
// loadEditData(): Promise<{ report, entries, occurrences, habits, moods, entryStatuses }>
// saveEntries(entries): Promise<{ entries }>   (throws { status: 422, errors } on validation failure)
// updateReport({ notes, mood }): Promise<void> (throws { status: 422, errors } on validation failure)

export const isReportGateway = (g) =>
    !!g &&
    typeof g.loadEditData === 'function' &&
    typeof g.saveEntries === 'function' &&
    typeof g.updateReport === 'function';
