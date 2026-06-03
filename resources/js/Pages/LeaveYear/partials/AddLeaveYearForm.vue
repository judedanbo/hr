<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const submitHandler = (data, node) => {
	router.post(route("leave-year.store"), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors([], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add leave year</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit
				type="number"
				name="year"
				label="Year"
				validation="required|number|between:2000,2100"
			/>
			<FormKit
				type="date"
				name="start_date"
				label="Start date"
				validation="required|date"
			/>
			<FormKit
				type="date"
				name="end_date"
				label="End date"
				validation="required|date"
			/>
			<FormKit type="checkbox" name="is_active" label="Active year" />
		</FormKit>
	</main>
</template>
