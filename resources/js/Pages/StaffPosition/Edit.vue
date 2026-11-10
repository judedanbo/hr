<script setup>
import { router } from "@inertiajs/vue3";
import StaffPositionForm from "./partials/StaffPositionForm.vue";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	staff: { type: Object, required: true },
	institution: { type: Number, required: true },
	staffPosition: { type: Object, required: true },
});

const submitHandler = (data, node) => {
	router.patch(
		route("staff.position.update", {
			staff: props.staff.id,
			staffPosition: props.staffPosition.id,
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Change Staff Position</h1>
		<FormKit
			type="form"
			submit-label="Save"
			:value="{
				position_id: staffPosition.position_id,
				start_date: staffPosition.start_date,
				end_date: staffPosition.end_date,
			}"
			@submit="submitHandler"
		>
			<StaffPositionForm :institution="institution" />
		</FormKit>
	</main>
</template>
