<script>
export default {
    name: "AppCheckboxField",
}
</script>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    modelValue: {
        type: Boolean,
        default: false,
    },
    cssClass: String,
    cssFieldClass: String,
    cssLabelClass: String,
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
    <div class="flex items-center mb-4">
        <input
            :id="name"
            :name="name"
            :value="modelValue"
            type="checkbox"
            :checked="modelValue === true"
            :class="[cssClass, cssFieldClass]"
            @change="$emit('update:modelValue', Boolean($event.target.checked))"
        >
        <label
            :for="name"
            :class="cssLabelClass"
        >
            {{ label }}<span v-if="isRequired" class="text-red-500 ml-0.5">*</span>
        </label>
    </div>
</template>
