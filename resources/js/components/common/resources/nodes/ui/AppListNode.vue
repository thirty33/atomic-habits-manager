<script lang="ts">
export default { name: "AppListNode" };
</script>

<script setup lang="ts">
import AppNode from "./AppNode.vue";
import type { ListNode } from "../domain/node-schema";
import type { ListNodeController } from "../adapters/useListNode";

defineProps<{ node: ListNode; list: ListNodeController }>();
</script>

<template>
    <div class="flex flex-col gap-2.5 font-body">
        <article
            v-for="(item, index) in list.items"
            :key="item.key"
            class="bg-card rounded-xl border overflow-hidden transition-[border-color,box-shadow]"
            :class="list.expandedIndex === index
                ? 'border-brand-300 shadow-[0_8px_24px_-16px_rgba(31,82,71,0.25)]'
                : 'border-line-200'"
        >
            <header
                class="flex items-center gap-3 px-3.5 py-3 cursor-pointer select-none transition-colors"
                :class="list.expandedIndex === index ? 'bg-brand-50 hover:bg-brand-100' : 'hover:bg-paper'"
                @click="list.toggle(index)"
            >
                <svg
                    width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"
                    class="shrink-0 transition-transform"
                    :class="list.expandedIndex === index ? 'rotate-180 text-brand-700' : 'text-ink-500'"
                >
                    <path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="shrink-0 font-mono text-[10.5px] tracking-[0.08em] uppercase text-ink-400">
                    #{{ String(index + 1).padStart(2, "0") }}
                </span>
                <span class="flex-1 min-w-0 truncate text-[13.5px] text-ink-700">{{ list.summaryFor(index) }}</span>
                <button
                    v-if="list.canRemove"
                    type="button"
                    class="shrink-0 grid place-items-center w-[30px] h-[30px] rounded-md text-ink-500 hover:text-danger-2 hover:bg-danger-2/10 transition-colors"
                    title="Eliminar programación"
                    @click.stop="list.removeItem(index)"
                >
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3 4h10M6 4V3a1 1 0 011-1h2a1 1 0 011 1v1m-5 0v9a1 1 0 001 1h4a1 1 0 001-1V4M7 7v5M9 7v5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </header>

            <div v-show="list.expandedIndex === index" class="border-t border-line-100 px-4 pt-4 pb-5">
                <AppNode
                    :node="node.item_template"
                    :form="item.form"
                    :error-for="list.itemErrorFor(index)"
                    :css-class-for="list.itemCssClassFor(index)"
                />
            </div>
        </article>

        <button
            type="button"
            class="w-full flex items-center justify-center gap-2 py-3.5 rounded-[10px] border-[1.5px] border-dashed border-line-300 text-[13.5px] font-medium text-ink-700 hover:border-brand-700 hover:text-brand-800 hover:bg-brand-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            :disabled="!list.canAdd"
            @click="list.addItem()"
        >
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
            {{ (node.add_label || "Agregar").replace(/^\+\s*/, "") }}
        </button>
    </div>
</template>