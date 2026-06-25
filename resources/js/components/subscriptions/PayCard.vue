<script>
export default { name: 'PayCard' };
</script>

<script setup>
// Read-only Binance payment data card (destination email + amount). The email is
// copiable. Styled by the token-based `.pay-card`/`.pay-readonly`/`.copy-btn`
// classes in app.css.
defineProps({
    binanceEmail: {
        type: String,
        required: true,
    },
    amount: {
        type: String,
        default: '',
    },
    copied: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['copy']);
</script>

<template>
    <div class="pay-card p-4">
        <div class="font-mono text-[10.5px] tracking-[0.12em] uppercase text-ink-400 mb-2">Transferir a</div>
        <div class="pay-readonly">
            <span class="truncate">{{ binanceEmail }}</span>
            <button class="copy-btn" type="button" :title="copied ? 'Copiado' : 'Copiar correo'" @click="$emit('copy')">
                <svg v-if="copied" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                </svg>
            </button>
        </div>

        <div class="flex items-end justify-between mt-4">
            <div>
                <div class="font-mono text-[10.5px] tracking-[0.12em] uppercase text-ink-400 mb-1">Monto</div>
                <div class="font-mono text-[28px] leading-none text-ink-900 tracking-tight">{{ amount }}</div>
            </div>
            <span class="chip brand">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                </svg>
                Unlimited · mensual
            </span>
        </div>

        <div class="mt-3.5 pt-3.5 border-t border-line-200 flex items-start gap-2 text-[12.5px] text-ink-500 leading-snug">
            <span class="mt-px text-ink-400 shrink-0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="16" x2="12" y2="12" />
                    <line x1="12" y1="8" x2="12.01" y2="8" />
                </svg>
            </span>
            <span>Transfiere el monto a este correo en Binance y luego notifícanos.</span>
        </div>
    </div>
</template>
