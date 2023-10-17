<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
import StaffStatusForm from "./partials/StaffStatusForm.vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
    staff: Object,
    institution: Number,
});

let statuses = ref([]);

onMounted(async () => {
    const response = await axios.get(
        route("institution.statuses", { institution: props.institution })
    );
    statuses.value = response.data;
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    Inertia.post(route("staff-status.store", { staff: data.staff_id }), data, {
        preserveScroll: true,
        onSuccess: () => {
            node.reset();
            emit("formSubmitted");
        },
        onError: (errors) => {
            node.setErrors(["Error Submitting form"], errors);
        },
    });
};
</script>

<template>
    <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
        <h1 class="text-2xl pb-4 dark:text-gray-100">Change Status</h1>
        <FormKit @submit="submitHandler" type="form" submit-label="Save">
            <FormKit type="hidden" name="staff_id" :value="staff.id" />
            <FormKit type="hidden" name="institution_id" :value="institution" />
            <StaffStatusForm :institution="institution" />
        </FormKit>
    </main>
</template>
