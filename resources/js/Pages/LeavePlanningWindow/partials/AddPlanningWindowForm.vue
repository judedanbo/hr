<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);
defineProps({
	leaveYears: { type: Array, default: () => [] },
});

const submitHandler = (data, node) => {
	router.post(route("leave-planning-window.store"), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Open planning window</h1>
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
				type="datetime-local"
				name="opens_at"
				label="Opens at"
				validation="required"
			/>
			<FormKit
				type="datetime-local"
				name="closes_at"
				label="Closes at"
				validation="required"
			/>
			<FormKit
				type="textarea"
				name="instructions"
				label="Instructions (optional)"
			/>
			<FormKit
				type="checkbox"
				name="allow_after_close"
				label="Allow entries after the close date"
			/>
			<FormKit
				type="checkbox"
				name="require_full_plan"
				label="Require staff to plan all assigned days before submitting"
			/>
		</FormKit>
	</main>
</template>
