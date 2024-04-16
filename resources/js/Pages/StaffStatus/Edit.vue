<script setup>
import { Inertia } from "@inertiajs/inertia";
import StaffStatusForm from "./partials/StaffStatusForm.vue";
const emit = defineEmits(["formSubmitted", "editHistory", "deleteHistory"]);
defineProps({
	statuses: Array,
	staffStatus: Object,
	staff: Object,
	institution: Number,
});
const submitHandler = (data, node) => {
	Inertia.patch(
		route("staff-status.update", {
			staff: data.id,
			staffStatus: data.id,
		}),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors([""], errors);
			},
		},
	);
};
</script>
<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Change Staff Status</h1>
		<FormKit
			@submit="submitHandler"
			type="form"
			submit-label="Save"
			:value="staffStatus"
		>
			<FormKit type="hidden" id="staff_id" name="staff_id" :value="staff.id" />
			<FormKit
				type="hidden"
				id="institution_id"
				name="institution_id"
				:value="institution"
			/>
			<FormKit
				type="hidden"
				id="hire_date"
				name="hire_date"
				:value="staff.hire_date"
			/>
			<StaffStatusForm :institution="institution" />
		</FormKit>
	</main>
</template>
