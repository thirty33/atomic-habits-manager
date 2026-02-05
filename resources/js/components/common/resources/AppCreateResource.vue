<script>
export default {
    name: "AppCreateResource",
}
</script>

<script setup>
import { reactive, computed, inject } from "vue";
import useForm from "@/composables/useForm.js";
import { AppForm } from "@/components/common";
import AppStepForm from "@/components/common/resources/AppStepForm.vue";

const props = defineProps({
    model: Object,
    modalData: Object,
});

const emit = defineEmits(["processed", "close"]);

const addSuccessToast = inject("addSuccessToast");
const addErrorToast = inject("addErrorToast");

const hasSteps = computed(() => props.modalData?.steps?.length > 0);

const form = reactive({});
const formFields = props?.modalData?.form_fields;

if (formFields) {
    formFields.forEach((field) => {
        const fieldName = field.props?.name;
        if (typeof field.props?.defaultValue !== "undefined")
        {
            form[fieldName] = field.props?.defaultValue;
            return;
        }

        form[fieldName] = props.model?.[fieldName] || "";
    });
}

const { cssClassFor, errorFor, submit, isSubmitting } = useForm(
    props?.modalData?.action?.url,
    props?.modalData?.action?.method,
    form,
    {
        headers: props?.modalData?.action?.headers || {},
    }
);

const submitForm = () => {
    submit((response) => {
        addSuccessToast(
            response?.title,
            response?.message,
            response?.timeout,
        );

        emit("processed");
    }, (message) => {
        addErrorToast(
            "Error",
            message,
        );
    });
};
</script>

<template>
    <AppStepForm
        v-if="hasSteps"
        :steps="modalData.steps"
        @processed="emit('processed')"
        @close="emit('close')"
    />
    <AppForm
        v-else
        :title="modalData?.title"
        :form="form"
        :form-fields="modalData?.form_fields"
        :css-class-for="cssClassFor"
        :error-for="errorFor"
        :is-submitting="isSubmitting"
        :text-submit-button="modalData?.text_submit_button"
        @submit="submitForm"
    />
</template>
