<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);
defineProps({
	leaveYears: { type: Array, default: () => [] },
});

const submitHandler = (data, node) => {
	router.post(route("holiday.store"), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => node.setErrors([], errors),
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add holiday</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit
				type="select"
				name="leave_year_id"
				label="Leave year"
				placeholder="Select year"
				:options="leaveYears"
				validation="required"
			/>
			<FormKit
				type="date"
				name="date"
				label="Date"
				validation="required|date"
			/>
			<FormKit
				type="text"
				name="name"
				label="Name"
				validation="required|string"
			/>
			<FormKit type="checkbox" name="is_recurring" label="Recurs every year" />
		</FormKit>
	</main>
</template>
