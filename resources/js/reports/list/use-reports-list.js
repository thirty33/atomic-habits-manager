// Reports list application controller — reactive list state + filters + pagination.

import { reactive } from 'vue';

export function useReportsList(gateway) {
    const state = reactive({
        rows: [],
        meta: { current_page: 1, last_page: 1, total: 0, from: 0, to: 0 },
        moods: [],
        filters: { date_range_from: '', date_range_to: '', mood: '' },
        loading: true,
    });

    function activeFilters() {
        const f = {};
        if (state.filters.date_range_from) f.date_range_from = state.filters.date_range_from;
        if (state.filters.date_range_to) f.date_range_to = state.filters.date_range_to;
        if (state.filters.mood) f.mood = state.filters.mood;
        return f;
    }

    async function load(page) {
        state.loading = true;
        try {
            const { rows, meta, moods } = await gateway.loadPage({
                ...activeFilters(),
                page: page ?? state.meta.current_page,
            });
            state.rows = rows;
            state.meta = { ...state.meta, ...meta };
            if (moods.length) {
                state.moods = moods;
            }
        } catch (e) {
            console.error('Error loading reports', e);
        } finally {
            state.loading = false;
        }
    }

    function applyFilters() {
        return load(1);
    }

    function clearFilters() {
        state.filters.date_range_from = '';
        state.filters.date_range_to = '';
        state.filters.mood = '';
        return load(1);
    }

    function goPage(page) {
        if (page >= 1 && page <= state.meta.last_page && page !== state.meta.current_page) {
            return load(page);
        }
        return undefined;
    }

    async function remove(row) {
        if (!window.confirm('¿Eliminar este reporte diario?')) {
            return;
        }
        try {
            await gateway.removeReport(row.delete_action);
            await load();
        } catch (e) {
            console.error('Error deleting report', e);
        }
    }

    return { state, load, applyFilters, clearFilters, goPage, remove };
}
