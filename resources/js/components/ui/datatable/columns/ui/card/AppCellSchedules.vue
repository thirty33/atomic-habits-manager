<script lang="ts">
export default { name: "AppCellSchedulesCard" };
</script>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { CellViewModel } from "../../contracts/cell";

const props = defineProps<{
    vm: Extract<CellViewModel, { kind: "schedules" }>;
    label?: string;
}>();

const MAX_VISIBLE = 3;
const open = ref(false);

const overflow = computed(() => props.vm.items.length > MAX_VISIBLE);
const visible = computed(() => (open.value ? props.vm.items : props.vm.items.slice(0, MAX_VISIBLE)));
const hidden = computed(() => props.vm.items.length - MAX_VISIBLE);
</script>

<template>
    <div class="py-1">
        <dt class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400 mb-1">{{ label }}</dt>
        <div v-if="vm.items.length">
            <ul class="space-y-2">
                <li v-for="(item, index) in visible" :key="item.id ?? index" class="flex flex-col gap-0.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-brand-50 text-brand-800">
                            {{ item.recurrenceLabel }}
                        </span>
                        <span class="font-mono text-[12px] text-ink-900 tabular-nums">{{ item.timeRange }}</span>
                    </div>
                    <span v-if="item.detail" class="text-[11.5px] text-ink-500 pl-0.5">{{ item.detail }}</span>
                </li>
            </ul>
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
        <span v-else class="italic text-ink-400 text-[12px]">{{ vm.emptyText }}</span>
    </div>
</template>
