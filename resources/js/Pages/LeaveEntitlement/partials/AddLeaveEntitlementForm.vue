<script setup>
import { router } from "@inertiajs/vue3";
import { computed } from "vue";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	leaveYears: { type: Array, default: () => [] },
	leaveTypes: { type: Array, default: () => [] },
	jobCategories: { type: Array, default: () => [] },
});

const categoryOptions = computed(() => [
	{ value: "", label: "All categories (default)" },
	...props.jobCategories,
]);

const submitHandler = (data, node) => {
	const payload = { ...data, job_category_id: data.job_category_id || null };
	router.post(route("leave-entitlement.store"), payload, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add entitlement</h1>
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
				type="select"
				name="leave_type_id"
				label="Leave type"
				placeholder="Select type"
				:options="leaveTypes"
				validation="required"
			/>
			<FormKit
				type="select"
				name="job_category_id"
				label="Job category"
				:options="categoryOptions"
			/>
			<FormKit
				type="number"
				name="days_allowed"
				label="Days allowed"
				validation="required|number|min:0|max:366"
			/>
			<FormKit
				type="number"
				name="min_service_months"
				label="Minimum service (months)"
				value="0"
				validation="required|number|min:0"
			/>
			<FormKit type="textarea" name="notes" label="Notes (optional)" />
		</FormKit>
	</main>
</template>
