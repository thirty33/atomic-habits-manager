<script>
export default {
    name: 'AppNumberField',
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
    modelValue: [String, Number],
    defaultValue: [String, Number],
    min: {
        type: Number,
        default: null,
    },
    max: {
        type: Number,
        default: null,
    },
    isRequired: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
})

defineEmits(['update:modelValue'])

const currentValue = computed(() => {
    return props.modelValue ?? props.defaultValue ?? '';
})
</script>

<template>
    <div>
        <label :for="name" :class="cssLabelClass">{{ label }}<span v-if="isRequired" class="text-red-500 ml-0.5">*</span></label>
        <input
            :id="name"
            :name="name"
            :value="currentValue"
            type="number"
            :min="min"
            :max="max"
            :class="[cssClass, cssFieldClass]"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <AppFormError
            v-if="error"
            :error="error"
        />
    </div>
</template>