<script setup>
import { router } from "@inertiajs/vue3";
import { computed } from "vue";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	entitlement: { type: Object, required: true },
	leaveYears: { type: Array, default: () => [] },
	leaveTypes: { type: Array, default: () => [] },
	jobCategories: { type: Array, default: () => [] },
});

const categoryOptions = computed(() => [
	{ value: "", label: "All categories (default)" },
	...props.jobCategories,
]);

const initial = computed(() => ({
	leave_year_id: props.entitlement.leave_year_id,
	leave_type_id: props.entitlement.leave_type_id,
	job_category_id: props.entitlement.job_category_id ?? "",
	days_allowed: props.entitlement.days_allowed,
	min_service_months: props.entitlement.min_service_months,
	notes: props.entitlement.notes,
}));

const submitHandler = (data, node) => {
	const payload = { ...data, job_category_id: data.job_category_id || null };
	router.patch(
		route("leave-entitlement.update", {
			leaveEntitlement: props.entitlement.id,
		}),
		payload,
		{
			preserveScroll: true,
			onSuccess: () => emit("formSubmitted"),
			onError: (errors) => node.setErrors([], errors),
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit entitlement</h1>
		<FormKit
			type="form"
			submit-label="Update"
			:value="initial"
			@submit="submitHandler"
		>
			<FormKit
				type="select"
				name="leave_year_id"
				label="Leave year"
				:options="leaveYears"
				validation="required"
			/>
			<FormKit
				type="select"
				name="leave_type_id"
				label="Leave type"
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
				validation="required|number|min:0"
			/>
			<FormKit type="textarea" name="notes" label="Notes (optional)" />
		</FormKit>
	</main>
</template>
