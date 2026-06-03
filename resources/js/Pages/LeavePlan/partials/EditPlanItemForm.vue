<script setup>
import { router } from "@inertiajs/vue3";
import { reactive, ref, watch } from "vue";
import axios from "axios";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	item: { type: Object, required: true },
	leaveTypes: { type: Array, default: () => [] },
});

const form = reactive({
	leave_type_id: props.item.leave_type_id,
	start_date: props.item.start_date,
	end_date: props.item.end_date,
	note: props.item.note ?? "",
});

const previewDays = ref(props.item.proposed_days ?? null);

watch(
	() => [form.leave_type_id, form.start_date, form.end_date],
	async () => {
		if (!form.leave_type_id || !form.start_date || !form.end_date) {
			previewDays.value = null;
			return;
		}
		try {
			const { data } = await axios.get(route("leave-plan.preview-days"), {
				params: {
					leave_type_id: form.leave_type_id,
					start_date: form.start_date,
					end_date: form.end_date,
				},
			});
			previewDays.value = data.days;
		} catch (e) {
			previewDays.value = null;
		}
	},
);

const submitHandler = (data, node) => {
	router.patch(
		route("leave-plan.items.update", { item: props.item.id }),
		form,
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit planned leave</h1>
		<FormKit type="form" submit-label="Update" @submit="submitHandler">
			<FormKit
				v-model="form.leave_type_id"
				type="select"
				name="leave_type_id"
				label="Leave type"
				:options="leaveTypes"
				validation="required"
			/>
			<FormKit
				v-model="form.start_date"
				type="date"
				name="start_date"
				label="Start date"
				validation="required|date"
			/>
			<FormKit
				v-model="form.end_date"
				type="date"
				name="end_date"
				label="End date"
				validation="required|date"
			/>
			<p
				v-if="previewDays !== null"
				class="text-sm text-gray-600 dark:text-gray-200 pb-2"
			>
				Countable leave days: <strong>{{ previewDays }}</strong>
			</p>
			<FormKit
				v-model="form.note"
				type="textarea"
				name="note"
				label="Note (optional)"
			/>
		</FormKit>
	</main>
</template>
