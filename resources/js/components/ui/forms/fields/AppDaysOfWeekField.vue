<script>
export default {
    name: 'AppDaysOfWeekField',
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
    cssLabelClass: String,
    modelValue: {
        type: Array,
        default: () => [],
    },
    defaultValue: {
        type: Array,
        default: () => [],
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

const emit = defineEmits(['update:modelValue'])

const days = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 0, label: 'Dom' },
]

const selectedDays = computed(() => {
    return props.modelValue?.length ? props.modelValue : (props.defaultValue || []);
})

const toggleDay = (dayValue) => {
    const current = [...selectedDays.value];
    const index = current.indexOf(dayValue);

    if (index === -1) {
        current.push(dayValue);
    } else {
        current.splice(index, 1);
    }

    emit('update:modelValue', current);
}

const isSelected = (dayValue) => {
    return selectedDays.value.includes(dayValue);
}
</script>

<template>
    <div>
        <label :class="cssLabelClass">{{ label }}<span v-if="isRequired" class="text-red-500 ml-0.5">*</span></label>
        <div class="flex flex-wrap gap-2 mt-2">
            <button
                v-for="day in days"
                :key="day.value"
                type="button"
                :class="[
                    'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                    isSelected(day.value)
                        ? 'bg-btn-primary text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                ]"
                @click="toggleDay(day.value)"
            >
                {{ day.label }}
            </button>
        </div>
        <AppFormError
            v-if="error"
            :error="error"
        />
    </div>
</template>