<script>
export default {
    name: 'AppTextareaField',
}
</script>

<script setup>
import { AppFormError } from '@/components/ui/forms/errors';

defineProps({
    label: String,
    name: String,
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    rows: {
        type: Number,
        default: 3,
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    cssClass: String,
    cssFieldClass: String,
    cssLabelClass: String,
    maxLength: {
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
    }
})

defineEmits(['update:modelValue'])
</script>

<template>
    <label
        :for="name"
        :class="cssLabelClass"
    >
        {{ label }}<span v-if="isRequired" class="text-red-500 ml-0.5">*</span>
    </label>

    <div class="mt-1 relative rounded-md shadow-sm">
        <textarea
            :id="name"
            :name="name"
            :value="modelValue"
            :rows="rows"
            :placeholder="placeholder"
            :disabled="disabled"
            :maxlength="maxLength"
            :class="[cssClass, cssFieldClass]"
            @input="$emit('update:modelValue', $event.target.value)"
        />

        <AppFormError
            v-if="error"
            :error="error"
        />
    </div>
</template>
