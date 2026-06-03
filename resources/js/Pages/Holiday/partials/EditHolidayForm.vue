<script setup>
import { router } from "@inertiajs/vue3";
import { computed } from "vue";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	holiday: { type: Object, required: true },
	leaveYears: { type: Array, default: () => [] },
});

const initial = computed(() => ({
	leave_year_id: props.holiday.leave_year_id,
	date: props.holiday.date,
	name: props.holiday.name,
	is_recurring: props.holiday.is_recurring,
}));

const submitHandler = (data, node) => {
	router.patch(route("holiday.update", { holiday: props.holiday.id }), data, {
		preserveScroll: true,
		onSuccess: () => emit("formSubmitted"),
		onError: (errors) => node.setErrors([], errors),
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit holiday</h1>
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
