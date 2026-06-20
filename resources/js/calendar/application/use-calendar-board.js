// Calendar application controller — reactive state + use cases.
// The Vue component stays thin and delegates everything here; data access is
// behind the injected gateway port (see infrastructure/http/HttpCalendarGateway).

import { computed, ref, shallowRef } from 'vue';
import { CalendarDate, nowDecimalHour } from '../domain/calendar-date.js';
import { rangeFor, stepAnchor } from '../domain/navigation.js';

export function useCalendarBoard(gateway) {
    const anchor = ref(CalendarDate.today());
    const view = ref('semana');
    const blocks = shallowRef([]);
    const loading = ref(false);
    const error = ref(null);
    const now = ref(nowDecimalHour());
    const today = ref(CalendarDate.today());

    const range = computed(() => rangeFor(view.value, anchor.value));

    async function reload() {
        loading.value = true;
        error.value = null;
        try {
            blocks.value = await gateway.loadBlocks(range.value);
        } catch (e) {
            error.value = e;
            blocks.value = [];
        } finally {
            loading.value = false;
        }
    }

    function goPrev() {
        anchor.value = stepAnchor(view.value, anchor.value, -1);
        return reload();
    }

    function goNext() {
        anchor.value = stepAnchor(view.value, anchor.value, 1);
        return reload();
    }

    function goToday() {
        anchor.value = CalendarDate.today();
        return reload();
    }

    function setView(next) {
        if (next === view.value) {
            return undefined;
        }
        view.value = next;
        return reload();
    }

    function selectDay(date) {
        anchor.value = date;
        if (view.value === 'mes' || view.value === 'lista') {
            view.value = 'dia';
        }
        return reload();
    }

    function tickClock() {
        now.value = nowDecimalHour();
        today.value = CalendarDate.today();
    }

    return {
        anchor, view, blocks, loading, error, now, today, range,
        reload, goPrev, goNext, goToday, setView, selectDay, tickClock,
    };
}
