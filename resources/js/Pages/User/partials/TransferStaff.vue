<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	staff: {
		type: Number,
		required: true,
	},
	institution: {
		type: Number,
		required: true,
	},
});

let units = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.unit-list", { institution: props.institution }),
	);
	units.value = response.data;
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 50), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	Inertia.post(route("staff.transfer.store", { staff: data.staff_id }), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Transfer Staff</h1>
		<FormKit submit-label="Save" type="form" @submit="submitHandler">
			<FormKit type="hidden" name="staff_id" :value="staff" />
			<FormKit
				id="unit_id"
				name="unit_id"
				type="select"
				validation="required|integer|min:1|max:300"
				label="New Location"
				placeholder="Select new location"
				:options="units"
				error-visibility="submit"
			/>
			<div class="sm:flex gap-4">
				<FormKit
					id="start_date"
					name="start_date"
					type="date"
					:min="end_date"
					:max="start_date"
					label="Assumption Date"
					:validation="'date_after:' + end_date + '|date_before:' + start_date"
					inner-class="w-1/2"
				/>
			</div>
			<FormKit
				id="remarks"
				name="remarks"
				type="text"
				label="Remarks"
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
