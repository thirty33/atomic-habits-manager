<script>
export default { name: 'CalendarBoard' };
</script>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { HttpCalendarGateway } from '@/calendar/infrastructure/http/http-calendar-gateway.js';
import { useCalendarBoard } from '@/calendar/application/use-calendar-board.js';
import { rangeLabel } from '@/calendar/domain/navigation.js';
import CalToolbar from '@/components/calendar/CalToolbar.vue';
import CalendarBlockDetail from '@/components/calendar/CalendarBlockDetail.vue';
import WeekView from '@/components/calendar/views/WeekView.vue';
import DayView from '@/components/calendar/views/DayView.vue';
import MonthView from '@/components/calendar/views/MonthView.vue';
import AgendaView from '@/components/calendar/views/AgendaView.vue';

const props = defineProps({
    jsonUrl: { type: String, required: true },
    blocksUrl: { type: String, required: true },
});

const { view, blocks, anchor, now, today, loading, reload, goPrev, goNext, goToday, setView, selectDay, tickClock } =
    useCalendarBoard(new HttpCalendarGateway(props.blocksUrl));

const title = computed(() => rangeLabel(view.value, anchor.value));
const viewComponent = computed(
    () => ({ mes: MonthView, semana: WeekView, dia: DayView, lista: AgendaView })[view.value]
);

const selected = ref(null);
function onSelect(id) {
    selected.value = blocks.value.find((b) => b.id === id) ?? null;
}

let timer = null;
onMounted(() => {
    reload();
    timer = setInterval(tickClock, 60000);
});
onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <div class="p-4 lg:p-6">
        <div class="mb-4">
            <p class="eyebrow">Backoffice</p>
            <h1 class="font-display text-[28px] leading-tight text-ink-900">Calendario</h1>
            <p class="text-[13px] text-ink-500">Seguimiento diario de tus hábitos</p>
        </div>

        <CalToolbar
            :view="view"
            :title="title"
            :loading="loading"
            @prev="goPrev"
            @next="goNext"
            @today="goToday"
            @change="setView"
        />

        <component
            :is="viewComponent"
            :blocks="blocks"
            :anchor="anchor"
            :now="now"
            :today="today"
            @select-day="selectDay"
            @select="onSelect"
        />

        <CalendarBlockDetail v-if="selected" :block="selected" @close="selected = null" />
    </div>
</template>
