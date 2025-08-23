<script setup>
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { format, addDays, subYears } from "date-fns";

const emit = defineEmits(["formSubmitted"]);
let unitTypes = ref([]);
const props = defineProps({
	unit: Number,
});
const selectedUnit = ref({});
const unitList = ref([]);
onMounted(async () => {
	unitTypes.value = (await axios.get(route("unit-type.index"))).data;
	selectedUnit.value = (
		await axios.get(route("unit.details", { unit: props.unit }))
	).data;
	unitList.value = (await axios.get(route("units.list"))).data;
});

const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	router.patch(route("unit.update", { unit: data.id }), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Unit</h1>
		<FormKit
			v-model="selectedUnit"
			type="form"
			submit-label="Save"
			@submit="submitHandler"
		>
			<FormKit
				id="name"
				type="text"
				name="name"
				label="Unit name"
				validation="required|string|length:2,150"
				validation-visibility="submit"
			/>
			<FormKit
				id="institution_id"
				type="hidden"
				name="institution_id"
				validation="required|integer|min:1|max:150"
				validation-visibility="submit"
				disabled
			/>
			<FormKit
				id="id"
				type="hidden"
				name="id"
				validation="required|integer|min:1|max:1000"
				validation-visibility="submit"
				disabled
			/>
			<div class="lg:flex gap-x-4">
				<FormKit
					id="type"
					type="select"
					name="type"
					validation="string|length:1,5"
					placeholder="Select Unit type"
					label="Unit Type"
					:options="unitTypes"
					error-visibility="submit"
				/>
				<FormKit
					id="unit_id"
					type="select"
					name="unit_id"
					:options="unitList"
					validation="integer|min:1|max:300"
					label="Parent Unit"
					error-visibility="submit"
				/>
			</div>
			<div class="sm:flex gap-4">
				<FormKit
					id="start_date"
					type="date"
					name="start_date"
					:max="start_date"
					label="Start date"
					:validation="'date_before:' + start_date"
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
