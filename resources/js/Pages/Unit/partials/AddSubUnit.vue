<script setup>
import { onMounted, ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
const emit = defineEmits(["formSubmitted"]);
let unitTypes = ref([]);
onMounted(async () => {
	const unitTypesData = await axios.get(route("unit-type.index"));
	unitTypes.value = unitTypesData.data;
});
const props = defineProps({
	unit: Number,
	institution: Object,
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	Inertia.post(route("unit.add-sub", { unit: props.unit }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["Error on submission"], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add sub unit</h1>
		<FormKit @submit="submitHandler" type="form" submit-label="Save">
			<FormKit
				type="text"
				name="name"
				id="name"
				label="Unit name"
				validation="required|string|length:2,150"
				validation-visibility="submit"
			/>
			<FormKit
				type="hidden"
				name="unit_id"
				id="unit_id"
				label="Unit"
				:value="unit"
				validation="required|integer|min:1|max:150"
				validation-visibility="submit"
				disabled
			/>
			{{ institution }}
			<!-- <FormKit

				type="hidden"
				name="institution"
				id="institution"
				label="Institution"
				:value="institution.name"
				validation="required|string|length:1,150"
				validation-visibility="submit"
				disabled
			/> -->
			<FormKit
				type="select"
				name="type"
				id="type"
				validation="required|string|length:1,5"
				placeholder="Select Unit type"
				label="Unit Type"
				:options="unitTypes"
				error-visibility="submit"
			/>
			<!-- <FormKit
				type="select"
				name="unit_id"
				id="unit_id"
				validation="integer|min:1|max:30"
				label="Parent Unit"
				:options="units"
				error-visibility="submit"
			/> -->
			<div class="sm:flex gap-4">
				<FormKit
					type="date"
					name="start_date"
					id="start_date"
					:value="today"
					:min="end_date"
					:max="start_date"
					label="Start date"
					:validation="
						'required|date_after:' + end_date + '|date_before:' + start_date
					"
					validation-visibility="submit"
					inner-class="w-1/2"
				/>
			</div>
			<!-- <FormKit
        type="text"
        name="remarks"
        id="remarks"
        label="Remarks"
        validation="string|length:2,120"
        validation-visibility="submit" /> -->
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
