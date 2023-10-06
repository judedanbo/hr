<script setup>
import { Inertia } from "@inertiajs/inertia";
const emit = defineEmits(["formSubmitted"]);

import { format, addDays, subYears } from "date-fns";

defineProps({
    qualifications: Array,
    person: Number,
});
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    Inertia.post(route("qualification.store"), data, {
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
    <main class="p-8 bg-gray-100 dark:bg-gray-700">
        <h1 class="text-2xl pb-4 dark:text-gray-100">Add Qualification</h1>
        <FormKit @submit="submitHandler" type="form" submit-label="Save">
            <FormKit
                type="hidden"
                name="person_id"
                id="person_id"
                :value="person"
            />
            <FormKit
                type="text"
                name="course"
                id="course"
                label="Course"
                validation="required|string|length:2,100"
                validation-visibility="submit"
            />
            <FormKit
                type="text"
                name="institution"
                id="institution"
                label="Institution"
                validation="string|length:2,100"
                validation-visibility="submit"
            />
            <FormKit
                type="text"
                name="qualification"
                id="qualification"
                label="Qualification"
                validation="string|length:2,100"
                validation-visibility="submit"
            />
            <FormKit
                type="text"
                name="qualification_number"
                id="qualification_number"
                label="Qualification Number"
                validation="string|length:2,100"
                validation-visibility="submit"
            />
            <FormKit
                type="text"
                name="level"
                id="level"
                label="Level"
                validation="string|length:2,100"
                validation-visibility="submit"
            />
            <FormKit
                type="text"
                name="year"
                id="year"
                label="Year of Graduation"
                validation="string|length:2,100"
                validation-visibility="submit"
            />
        </FormKit>
    </main>
</template>

<style scoped>
.formkit-outer {
    @apply w-full;
}
.formkit-submit {
    @apply justify-self-end;
}
.formkit-actions {
    @apply flex justify-end;
}
</style>
