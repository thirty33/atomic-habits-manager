<script>
export default {
    name: 'AppDateField',
}
</script>

<script setup>
import { AppFormError } from '@/components/ui/forms/errors';
import { computed } from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    cssClass: String,
    cssFieldClass: String,
    cssLabelClass: String,
    modelValue: String,
    defaultValue: String,
    error: {
        type: String,
        default: null,
    },
})

defineEmits(['update:modelValue'])

const currentValue = computed(() => {
    return props.modelValue || props.defaultValue || '';
})
</script>

<template>
    <div>
        <label :for="name" :class="cssLabelClass">{{ label }}</label>
        <input
            :id="name"
            :name="name"
            :value="currentValue"
            type="date"
            :class="[cssClass, cssFieldClass]"
            @change="$emit('update:modelValue', $event.target.value)"
        />
        <AppFormError
            v-if="error"
            :error="error"
        />
    </div>
</template>
