<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	staff: Number,
	institution: Number,
});

let statuses = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.statuses", { institution: props.institution }),
	);
	statuses.value = response.data;
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	router.post(route("staff-status.store", { staff: data.staff_id }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors([""], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Change Status</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit type="hidden" name="staff_id" :value="staff" />
			<FormKit type="hidden" name="institution_id" :value="institution" />
			<FormKit
				id="status"
				type="select"
				name="status"
				validation="required|string"
				label="Status"
				placeholder="Select Status"
				:options="statuses"
				error-visibility="submit"
			/>
			<div class="sm:flex gap-4">
				<FormKit
					id="start_date"
					type="date"
					name="start_date"
					:value="today"
					:min="end_date"
					:max="start_date"
					label="Start date"
					:validation="
						'required|date|date_after:' +
						end_date +
						'|date_before:' +
						start_date
					"
					validation-visibility="submit"
					inner-class="w-1/2"
				/>
				<FormKit
					id="end_date"
					type="date"
					name="end_date"
					:min="today"
					label="End date"
					:validation="'date|date_after:' + today"
					validation-visibility="submit"
					inner-class="w-1/2"
				/>
			</div>
			<FormKit
				id="description"
				type="text"
				name="description"
				label="Description"
				validation="string|length:2,120"
				validation-visibility="submit"
			/>
		</FormKit>
	</main>
</template>

<style scoped>
.formkit-outer {
	@apply w-full;
}
.formkit-submit {
	@apply justify-self-end;
}
.formkit-actions {
	@apply flex justify-end;
}
</style>
