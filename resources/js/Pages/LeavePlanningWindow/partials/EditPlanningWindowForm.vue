<script setup>
import { router } from "@inertiajs/vue3";
import { computed } from "vue";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	window: { type: Object, required: true },
	leaveYears: { type: Array, default: () => [] },
});

const initial = computed(() => ({
	leave_year_id: props.window.leave_year_id,
	opens_at: (props.window.opens_at || "").replace(" ", "T"),
	closes_at: (props.window.closes_at || "").replace(" ", "T"),
	instructions: props.window.instructions,
	allow_after_close: props.window.allow_after_close,
	require_full_plan: props.window.require_full_plan,
}));

const submitHandler = (data, node) => {
	router.patch(
		route("leave-planning-window.update", {
			leavePlanningWindow: props.window.id,
		}),
		data,
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit planning window</h1>
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
