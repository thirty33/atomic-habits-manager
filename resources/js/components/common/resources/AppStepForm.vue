<script>
export default {
    name: "AppStepForm",
}
</script>

<script setup>
import { ref, reactive, computed, inject } from "vue";
import useComponentsLoader from "@/composables/useComponentsLoader.js";
import useAxios from "@/composables/useAxios.js";
import { AppSubmitButton } from '@/components/ui/forms/buttons';

const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["processed", "close"]);

const addSuccessToast = inject("addSuccessToast");
const addErrorToast = inject("addErrorToast");

const { findComponentByName, components, formFieldsLoader } = useComponentsLoader();

const currentStepIndex = ref(0);
const isSubmitting = ref(false);
const errors = ref({});
const submitted = ref(false);
const createdData = reactive({});

const currentStep = computed(() => props.steps[currentStepIndex.value]);

const allFormFields = computed(() => props.steps.flatMap(step => step.form_fields));
const loadedComponents = computed(() => components(allFormFields.value, formFieldsLoader));

const form = reactive({});

const initFormForStep = (stepIndex) => {
    Object.keys(form).forEach(key => delete form[key]);
    props.steps[stepIndex].form_fields.forEach((field) => {
        const fieldName = field.props?.name;
        if (typeof field.props?.defaultValue !== "undefined") {
            form[fieldName] = field.props.defaultValue;
            return;
        }
        form[fieldName] = "";
    });
};

initFormForStep(0);

const errorFor = (field) => errors.value[field] ? errors.value[field][0] : null;
const hasError = (field) => !!errorFor(field);

const visibleFields = computed(() => {
    return (currentStep.value?.form_fields || []).filter(field => {
        if (!field.visible_when) return true;
        return Object.entries(field.visible_when).every(([key, value]) => {
            if (Array.isArray(value)) return value.includes(form[key]);
            return form[key] === value;
        });
    });
});

const cssClassFor = (field) => {
    if (submitted.value && hasError(field)) {
        if (field.endsWith('_id')) {
            return 'border-red-500 dark:border-red-500';
        }
        return 'border-red-500 bg-red-100 dark:border-red-500 dark:bg-red-500';
    }
    return '';
};

const advanceOrFinish = () => {
    if (currentStepIndex.value >= props.steps.length - 1) {
        emit("processed");
    } else {
        currentStepIndex.value++;
        initFormForStep(currentStepIndex.value);
        errors.value = {};
        submitted.value = false;
    }
};

const submitStep = async () => {
    errors.value = {};
    isSubmitting.value = true;

    try {
        const { makeRequest } = useAxios();

        const requestData = { ...form };
        Object.keys(createdData).forEach((key) => {
            requestData[key] = createdData[key];
        });

        const { data } = await makeRequest({
            method: currentStep.value.action.method.toLowerCase(),
            url: currentStep.value.action.url,
            data: requestData,
        });

        if (data?.extra) {
            Object.assign(createdData, data.extra);
        }

        const isLast = currentStepIndex.value >= props.steps.length - 1;
        if (isLast) {
            addSuccessToast(data?.title, data?.message, data?.timeout);
        }

        advanceOrFinish();
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors;
        }
    } finally {
        submitted.value = true;
        isSubmitting.value = false;
    }
};

const skipStep = () => {
    advanceOrFinish();
};
</script>

<template>
    <div class="p-4 pt-10">
        <!-- Step indicators -->
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-500">
            <div class="flex items-center space-x-2">
                <span
                    v-for="(step, index) in steps"
                    :key="step.step"
                    :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors',
                        index === currentStepIndex
                            ? 'bg-btn-primary text-white'
                            : index < currentStepIndex
                                ? 'bg-green-500 text-white'
                                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                    ]"
                >
                    {{ step.step }}
                </span>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Paso {{ currentStepIndex + 1 }} de {{ steps.length }}
            </span>
        </div>

        <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-white">
            {{ currentStep.title }}
        </h2>

        <form @submit.prevent="submitStep">
            <div v-for="field in visibleFields" :key="field.uuid" class="mb-2">
                <component
                    :is="findComponentByName(field.component, loadedComponents)"
                    v-bind="field.props"
                    :css-class="cssClassFor(field.props.name)"
                    :error="errorFor(field.props.name)"
                    :model-value="form[field.props.name]"
                    @update:model-value="form[field.props.name] = $event"
                />
            </div>

            <div class="flex justify-between items-center mt-3">
                <button
                    v-if="currentStep.is_optional"
                    type="button"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 underline"
                    @click="skipStep"
                >
                    {{ currentStep.text_skip_button || 'Omitir' }}
                </button>
                <div v-else></div>

                <AppSubmitButton :loading="isSubmitting">
                    {{ currentStep.text_submit_button }}
                </AppSubmitButton>
            </div>
        </form>
    </div>
</template>