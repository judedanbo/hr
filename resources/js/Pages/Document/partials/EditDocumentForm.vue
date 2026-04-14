<script setup>
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
    document: { type: Object, required: true },
    documentTypes: { type: Array, default: () => [] },
    documentStatuses: { type: Array, default: () => [] },
});

const submitHandler = (data, node) => {
    router.post(route("document.update", { document: props.document.id }), {
        ...data,
        _method: 'PATCH',
    }, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { node.reset(); emit("formSubmitted"); },
        onError: (errors) => node.setErrors([""], errors),
    });
};
</script>

<template>
    <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
        <h1 class="text-2xl pb-4 dark:text-gray-100">Edit Document</h1>
        <FormKit type="form" submit-label="Update" @submit="submitHandler" :config="{ classes: { outer: 'mb-4' } }">
            <FormKit
                id="document_type"
                type="select"
                name="document_type"
                :value="document.document_type"
                :options="documentTypes.map(t => ({ label: t.label, value: t.value }))"
                validation="required"
                label="Document Type"
            />
            <FormKit
                id="document_title"
                type="text"
                name="document_title"
                :value="document.document_title"
                validation="required|length:3,100"
                label="Document Title"
                placeholder="Enter document title"
            />
            <FormKit
                id="document_number"
                type="text"
                name="document_number"
                :value="document.document_number"
                validation="length:0,20"
                label="Document Number (optional)"
                placeholder="Enter document number"
            />
            <FormKit
                id="file_name"
                type="file"
                name="file_name"
                label="Replace Document File (optional)"
                accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                help="Leave empty to keep current file. Accepted: PDF, PNG, JPG, DOC, DOCX (max 10MB)"
            />
            <FormKit
                id="document_status"
                type="select"
                name="document_status"
                :value="document.document_status"
                :options="documentStatuses.map(s => ({ label: s.label, value: s.value }))"
                validation="required"
                label="Status"
            />
            <FormKit
                id="document_remarks"
                type="textarea"
                name="document_remarks"
                :value="document.document_remarks"
                validation="length:0,255"
                label="Remarks (optional)"
                placeholder="Enter any additional remarks"
                rows="3"
            />
        </FormKit>
    </main>
</template>
