// Reports list infrastructure — HTTP adapter over the bootstrap axios instance.

export class HttpReportsListGateway {
    constructor(boardJsonUrl) {
        this.boardJsonUrl = boardJsonUrl;
    }

    async loadPage(params) {
        const { data } = await window.axios.get(this.boardJsonUrl, { params });
        return { rows: data.data ?? [], meta: data.meta ?? {}, moods: data.moods ?? [] };
    }

    async removeReport(action) {
        await window.axios.request({
            url: action.url ?? action.action ?? action,
            method: (action.method ?? 'delete').toLowerCase(),
        });
    }
}
