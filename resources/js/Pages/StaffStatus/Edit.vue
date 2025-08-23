<script setup>
import { router } from "@inertiajs/vue3";
import StaffStatusForm from "./partials/StaffStatusForm.vue";
const emit = defineEmits(["formSubmitted", "editHistory", "deleteHistory"]);
defineProps({
	statuses: Array,
	staffStatus: Object,
	staff: Object,
	institution: Number,
});
const submitHandler = (data, node) => {
	router.patch(
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
			type="form"
			submit-label="Save"
			:value="staffStatus"
			@submit="submitHandler"
		>
			<FormKit id="staff_id" type="hidden" name="staff_id" :value="staff.id" />
			<FormKit
				id="institution_id"
				type="hidden"
				name="institution_id"
				:value="institution"
			/>
			<FormKit
				id="hire_date"
				type="hidden"
				name="hire_date"
				:value="staff.hire_date"
			/>
			<StaffStatusForm :institution="institution" />
		</FormKit>
	</main>
</template>
