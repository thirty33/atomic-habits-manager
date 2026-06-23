// Reports infrastructure — HTTP adapter implementing the editor gateway port
// over the bootstrap axios instance. Maps the editJson contract and normalizes
// 422 validation errors into { status, errors } for the controller to surface.

function normalize(e) {
    if (e?.response?.status === 422) {
        return { status: 422, errors: e.response.data.errors };
    }
    return e;
}

export class HttpReportGateway {
    constructor({ jsonUrl, saveEntriesUrl, updateReportUrl }) {
        this.jsonUrl = jsonUrl;
        this.saveEntriesUrl = saveEntriesUrl;
        this.updateReportUrl = updateReportUrl;
    }

    async loadEditData() {
        const { data } = await window.axios.get(this.jsonUrl);
        return {
            report: data.report,
            entries: data.entries,
            occurrences: data.occurrences,
            habits: data.habits,
            moods: data.moods,
            entryStatuses: data.entry_statuses,
        };
    }

    async saveEntries(entries) {
        try {
            const { data } = await window.axios.put(this.saveEntriesUrl, { entries });
            return { entries: data?.extra?.entries ?? null };
        } catch (e) {
            throw normalize(e);
        }
    }

    async updateReport({ notes, mood }) {
        try {
            await window.axios.put(this.updateReportUrl, { notes, mood });
        } catch (e) {
            throw normalize(e);
        }
    }
}
