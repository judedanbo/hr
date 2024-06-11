<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);
import StaffTypeForm from "@/Pages/StaffType/partials/StaffTypeForm.vue";

const props = defineProps({
	staff: { type: Object, required: true },
	institution: { type: Number, required: true },
});

let types = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.staff-types", { institution: props.institution }),
	);
	types.value = response.data;
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	Inertia.post(route("staff-type.store", { staff: data.staff_id }), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Change Staff Type</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit type="hidden" name="staff_id" :value="staff.id" />
			<FormKit type="hidden" name="institution_id" :value="institution" />
			<StaffTypeForm :institution="institution" :hire_date="staff.hire_date" />
		</FormKit>
	</main>
</template>
