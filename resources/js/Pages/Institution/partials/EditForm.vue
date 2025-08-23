<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
const emit = defineEmits(["formSubmitted"]);

import { format, addYears, subYears } from "date-fns";

let props = defineProps({
	institutionName: String,
	institutionId: Number,
	unit: {
		type: Object,
		required: true,
	},
});
let unit_types = ref([]);
let unitList = ref([]);
onMounted(async () => {
	const unitTypesData = await axios.get(route("unit-type.index"));
	const unitListData = await axios.get(
		route("institution.unit-list", { institution: props.institutionId }),
	);
	unit_types.value = unitTypesData.data;
	unitList.value = unitListData.data;
});
const start_date = format(subYears(new Date(), 1), "yyyy-MM-dd");
const end_date = format(addYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	router.patch(route("unit.update", { unit: data.id }), data, {
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
			Edit Department/Section/Unit
		</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit
				id="institution_id"
				type="hidden"
				name="institution_id"
				:value="unit.institution_id"
			/>
			<FormKit id="id" type="hidden" name="id" :value="unit.id" />
			<FormKit
				id="unit_id"
				type="hidden"
				name="unit_id"
				:value="unit.unit_id"
			/>
			<FormKit
				id="name"
				type="text"
				name="name"
				:value="unit.name"
				label="Name of department/sec/unit"
				validation="required|string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				id="type"
				v-model="unit.type"
				type="select"
				name="type"
				label="Unit type"
				placeholder="Select unit type"
				validation="string|length:1,5"
				validation-visibility="submit"
			>
				<option
					v-for="unitType in unit_types"
					:id="unitType.value"
					:key="unitType.value"
					:name="unitType.value"
					:value="unitType.value"
				>
					{{ unitType.label }}
				</option>
			</FormKit>
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
				id="parent"
				v-model="unit.unit_id"
				type="select"
				name="parent"
				label="Parent department/sec/unit"
				placeholder="Select parent unit"
				validation="number|min:1|max:500"
				validation-visibility="submit"
			>
				<option v-for="unit in unitList" :key="unit.value" :value="unit.value">
					{{ unit.label }}
				</option>
			</FormKit>
			<FormKit
				id="start_date"
				type="date"
				name="start_date"
				label="Date Created"
				:min="start_date"
				:max="end_date"
				:value="unit.start_date?.substring(0, 10)"
				:validation="
					'date_after_or_equal :' +
					end_date +
					'|date_before_or_equal:' +
					start_date
				"
				validation-visibility="submit"
			/>
			<FormKit
				id="end_date"
				type="date"
				name="end_date"
				label="Date removed"
				:min="start_date"
				:max="end_date"
				:value="unit.end_date?.substring(0, 10)"
				:validation="
					'date_after_or_equal :' +
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
