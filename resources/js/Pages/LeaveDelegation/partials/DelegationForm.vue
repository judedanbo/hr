<script setup>
import { router } from "@inertiajs/vue3";
import { reactive } from "vue";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	mode: { type: String, default: "create" },
	delegation: { type: Object, default: null },
	staffOptions: { type: Array, default: () => [] },
});

const form = reactive({
	delegator_id: props.delegation?.delegator_id ?? "",
	delegate_id: props.delegation?.delegate_id ?? "",
	start_date: props.delegation?.start_date ?? "",
	end_date: props.delegation?.end_date ?? "",
	reason: props.delegation?.reason ?? "",
});

const submitHandler = (data, node) => {
	const onError = (errors) => node.setErrors([], errors);
	if (props.mode === "edit") {
		router.patch(
			route("leave-delegation.update", {
				leaveDelegation: props.delegation.id,
			}),
			form,
			{ preserveScroll: true, onSuccess: () => emit("formSubmitted"), onError },
		);
		return;
	}
	router.post(route("leave-delegation.store"), form, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError,
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">
			{{ mode === "edit" ? "Edit delegation" : "Add delegation" }}
		</h1>
		<FormKit
			type="form"
			:submit-label="mode === 'edit' ? 'Update' : 'Save'"
			@submit="submitHandler"
		>
			<FormKit
				v-model="form.delegator_id"
				type="select"
				name="delegator_id"
				label="Head delegating (away)"
				placeholder="Select staff"
				:options="staffOptions"
				validation="required"
			/>
			<FormKit
				v-model="form.delegate_id"
				type="select"
				name="delegate_id"
				label="Delegate (acting approver)"
				placeholder="Select staff"
				:options="staffOptions"
				validation="required"
			/>
			<FormKit
				v-model="form.start_date"
				type="date"
				name="start_date"
				label="From"
				validation="required|date"
			/>
			<FormKit
				v-model="form.end_date"
				type="date"
				name="end_date"
				label="To"
				validation="required|date"
			/>
			<FormKit
				v-model="form.reason"
				type="text"
				name="reason"
				label="Reason (optional)"
			/>
		</FormKit>
	</main>
</template>
