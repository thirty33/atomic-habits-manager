// Calendar infrastructure — HTTP adapter implementing the gateway port over
// the bootstrap axios instance. Maps the JSON contract into domain blocks.

import { CalendarBlock } from '../../domain/calendar-block.js';

export class HttpCalendarGateway {
    constructor(blocksUrl) {
        this.blocksUrl = blocksUrl;
    }

    async loadBlocks(range) {
        const { data } = await window.axios.get(this.blocksUrl, {
            params: { start: range.from.toISO(), end: range.to.toISO() },
        });
        return (data.data ?? []).map((dto) => new CalendarBlock(dto));
    }
}
