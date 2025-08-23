<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
const emit = defineEmits(["formSubmitted"]);

import { format, addYears, subYears } from "date-fns";

let props = defineProps({
	institutionName: String,
	institutionId: Number,
});
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(subYears(new Date(), 1), "yyyy-MM-dd");
const end_date = format(addYears(new Date(), 1), "yyyy-MM-dd");

let unitTypes = ref([]);
let allUnits = ref([]);
onMounted(async () => {
	const unitTypesData = await axios.get(route("unit-type.index"));
	const unitListData = await axios.get(
		route("institution.unit-list", { institution: props.institutionId }),
	);
	unitTypes.value = unitTypesData.data;
	allUnits.value = unitListData.data;
});

const submitHandler = (data, node) => {
	router.post(route("unit.store"), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["errors"], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">
			Add new Department/Section/Unit
		</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit
				id="institution_id"
				type="hidden"
				name="institution_id"
				:value="institutionId"
			/>
			<FormKit
				id="name"
				type="text"
				name="name"
				label="Name of department/sec/unit"
				validation="required|string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				id="type"
				type="select"
				name="type"
				placeholder="Select unit type"
				:options="unitTypes"
				label="Parent unit type"
				validation="string|length:1,5"
				validation-visibility="submit"
			/>
			<FormKit
				id="institution"
				type="hidden"
				name="institution"
				:value="institutionName"
				validation="string|length:2,100"
				validation-visibility="submit"
				disabled="true"
			/>
			<FormKit
				id="unit_id"
				type="select"
				name="unit_id"
				:options="allUnits"
				placeholder="Select parent department/sec/unit"
				label="Parent department/sec/unit"
				validation="number|min:1|max:500"
				validation-visibility="submit"
			/>
			<FormKit
				id="start_date"
				type="date"
				name="start_date"
				label="Date Created"
				:min="start_date"
				:max="end_date"
				:value="today"
				:validation="
					'required|date_after_or_equal :' +
					end_date +
					'|date_before_or_equal:' +
					start_date
				"
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
