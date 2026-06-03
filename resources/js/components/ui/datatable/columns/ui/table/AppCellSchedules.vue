<script lang="ts">
export default { name: "AppCellSchedulesTable" };
</script>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { CellViewModel } from "../../contracts/cell";

const props = defineProps<{ vm: Extract<CellViewModel, { kind: "schedules" }> }>();

const MAX_VISIBLE = 3;
const open = ref(false);

const overflow = computed(() => props.vm.items.length > MAX_VISIBLE);
const visible = computed(() => (open.value ? props.vm.items : props.vm.items.slice(0, MAX_VISIBLE)));
const hidden = computed(() => props.vm.items.length - MAX_VISIBLE);
</script>

<template>
    <td class="px-4 py-4 align-top min-w-[240px]">
        <div v-if="vm.items.length">
            <div class="grid items-center gap-x-2.5 gap-y-[7px] grid-cols-[max-content_max-content_auto]">
                <template v-for="(item, index) in visible" :key="item.id ?? index">
                    <span class="inline-flex items-center px-2.5 py-[3px] rounded-full text-[10.5px] font-medium bg-brand-50 text-brand-800 justify-self-start whitespace-nowrap">
                        {{ item.recurrenceLabel }}
                    </span>
                    <span class="font-mono text-[12.5px] text-ink-900 tabular-nums whitespace-nowrap">{{ item.timeRange }}</span>
                    <span class="text-[11.5px] text-ink-500 truncate">{{ item.detail }}</span>
                </template>
            </div>
            <button
                v-if="overflow"
                type="button"
                class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-brand-700 hover:text-brand-800"
                @click="open = !open"
            >
                <template v-if="open">
                    Ver menos
                    <svg width="10" height="10" viewBox="0 0 16 16" fill="none" class="rotate-180" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </template>
                <template v-else>
                    +{{ hidden }} más
                    <svg width="10" height="10" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </template>
            </button>
        </div>
        <span v-else class="italic text-ink-400 text-[12.5px]">{{ vm.emptyText }}</span>
    </td>
</template>
