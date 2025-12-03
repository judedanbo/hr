<script setup>
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
    contact: { type: Object, required: true },
    contactTypes: { type: Array, default: () => [] },
});

const submitHandler = (data, node) => {
    router.patch(route("contact.update", { contact: props.contact.id }), data, {
        preserveScroll: true,
        onSuccess: () => { node.reset(); emit("formSubmitted"); },
        onError: (errors) => node.setErrors([""], errors),
    });
};
</script>

<template>
    <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
        <h1 class="text-2xl pb-4 dark:text-gray-100">Edit Contact</h1>
        <FormKit type="form" submit-label="Update" @submit="submitHandler">
            <FormKit id="contact_type" type="select" name="contact_type" :value="contact.contact_type" :options="contactTypes.map(t => ({ label: t.label, value: t.value }))" validation="required" label="Contact Type" />
            <FormKit id="contact" type="text" name="contact" :value="contact.contact" validation="required|length:5,255" label="Contact" placeholder="Enter contact info" />
            <FormKit id="valid_end" type="date" name="valid_end" :value="contact.valid_end" label="Valid Until (optional)" />
        </FormKit>
    </main>
</template>
