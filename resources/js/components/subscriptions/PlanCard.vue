<script>
export default { name: 'PlanCard' };
</script>

<script setup>
// A single plan card (Free or Unlimited). Reuses the redesigned global classes
// (`.po-eyebrow`, `.chip`, `.btn`, `.font-serif`, `.font-mono`) plus the new
// token-based `.plan-card`/`.featured`/`.feat` classes in app.css.
import { computed } from 'vue';
import FeatureRow from './FeatureRow.vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    current: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['action']);

const featured = computed(() => props.plan.isUnlimited);
</script>

<template>
    <div :class="['plan-card p-7 flex flex-col', featured ? 'featured' : '']">
        <div class="flex items-start justify-between">
            <div>
                <div :class="['po-eyebrow mb-2', featured ? '!text-brand-700' : '']">
                    {{ featured ? 'Acceso completo' : 'Para empezar' }}
                </div>
                <h3 class="font-serif text-[34px] leading-none">
                    {{ featured ? 'Unlimited' : 'Free' }}
                </h3>
            </div>

            <span v-if="current" class="chip neutral"><span class="dot" />Tu plan</span>
            <span v-else-if="featured" class="chip brand">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                    <polygon points="12 2 15 9 22 9.5 17 14 18.5 21 12 17.5 5.5 21 7 14 2 9.5 9 9 12 2" />
                </svg>
                Recomendado
            </span>
        </div>

        <!-- Price -->
        <div class="mt-5 flex items-end gap-2">
            <span v-if="plan.isFree" class="font-serif text-[44px] leading-none text-ink-900">Gratis</span>
            <template v-else>
                <span class="font-mono text-[40px] leading-none text-ink-900 tracking-tight">{{ plan.formattedPrice }}</span>
                <span class="font-mono text-[14px] text-ink-500 mb-1.5">/ mes</span>
            </template>
        </div>
        <p class="text-[13px] text-ink-500 mt-2">
            {{ featured ? 'Todo lo de Free, sin límites, con asistente.' : 'Entras como invitado en Free por defecto.' }}
        </p>

        <div :class="['my-6 h-px', featured ? 'bg-line-200' : 'bg-line-100']" />

        <!-- Features -->
        <div class="flex-1">
            <FeatureRow
                v-for="(feat, index) in plan.features"
                :key="index"
                :kind="feat.kind"
                :label="feat.label"
                :pro="feat.pro || false"
            />
        </div>

        <!-- CTA -->
        <div class="mt-7">
            <button
                :class="featured ? 'btn btn-primary w-full justify-center !py-3' : 'btn btn-outline w-full justify-center !py-3'"
                type="button"
                @click="$emit('action')"
            >
                <template v-if="featured">
                    Subscribirme
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </template>
                <template v-else>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <polyline points="9 12 11 14 15 10" />
                    </svg>
                    Asegurar mi cuenta
                </template>
            </button>
            <p class="text-[12px] text-ink-400 mt-2.5 text-center leading-snug">
                {{ featured
                    ? 'Pago por cripto. Activación tras verificar el pago.'
                    : 'Reclama tu cuenta invitada (conserva tus hábitos). Sin pago.' }}
            </p>
        </div>
    </div>
</template>
