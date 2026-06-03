<script setup>
import { router } from "@inertiajs/vue3";
import { computed } from "vue";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	leaveYear: { type: Object, required: true },
});

const initial = computed(() => ({
	year: props.leaveYear.year,
	start_date: props.leaveYear.start_date,
	end_date: props.leaveYear.end_date,
	is_active: props.leaveYear.is_active,
}));

const submitHandler = (data, node) => {
	router.patch(
		route("leave-year.update", { leaveYear: props.leaveYear.id }),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors([], errors);
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit leave year</h1>
		<FormKit
			type="form"
			submit-label="Update"
			:value="initial"
			@submit="submitHandler"
		>
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
