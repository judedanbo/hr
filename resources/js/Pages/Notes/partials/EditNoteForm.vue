<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
    note: { type: Object, required: true },
    noteTypes: { type: Array, default: () => [] },
});

const submitHandler = (data, node) => {
    router.patch(route("notes.update", { note: props.note.id }), data, {
        preserveScroll: true,
        onSuccess: () => {
            node.reset();
            emit("formSubmitted");
        },
        onError: (errors) => {
            node.setErrors([""], errors);
        },
    });
};
</script>

<template>
    <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
        <h1 class="text-2xl pb-4 dark:text-gray-100">Edit Note</h1>
        <FormKit type="form" submit-label="Update Note" @submit="submitHandler">
            <FormKit
                id="note"
                type="textarea"
                name="note"
                :value="note.note"
                validation="required|length:1,1000"
                label="Note"
                placeholder="Enter your note..."
                rows="4"
            />
            <FormKit
                id="note_type"
                type="select"
                name="note_type"
                :value="note.note_type"
                :options="[
                    { label: 'Select type (optional)', value: '' },
                    ...noteTypes.map((t) => ({ label: t.label, value: t.value })),
                ]"
                label="Note Type"
            />
            <FormKit
                id="note_date"
                type="date"
                name="note_date"
                :value="note.note_date"
                label="Date"
            />
            <FormKit
                id="url"
                type="url"
                name="url"
                :value="note.url"
                validation="url"
                label="URL (optional)"
                placeholder="https://example.com"
            />
        </FormKit>
    </main>
</template>
