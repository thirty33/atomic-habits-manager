<script>
export default {
    name: 'AppTimeField',
}
</script>

<script setup>
import { ref } from 'vue';
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

const inputRef = ref(null)

const currentValue = computed(() => {
    return props.modelValue || props.defaultValue || '';
})

const openPicker = () => {
    inputRef.value?.showPicker()
}
</script>

<template>
    <div>
        <label :for="name" :class="cssLabelClass">{{ label }}<span v-if="isRequired" class="text-red-500 ml-0.5">*</span></label>
        <div class="relative">
            <input
                ref="inputRef"
                :id="name"
                :name="name"
                :value="currentValue"
                type="time"
                :class="[cssClass, cssFieldClass, 'pr-10']"
                @change="$emit('update:modelValue', $event.target.value)"
            />
            <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                tabindex="-1"
                @click="openPicker"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </button>
        </div>
        <AppFormError
            v-if="error"
            :error="error"
        />
    </div>
</template>

<style scoped>
input[type="time"]::-webkit-calendar-picker-indicator {
    display: none;
}
</style>
