<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	position: { type: Number, required: true },
	// positionPositions: {
	// 	type: Array,
	// 	default: () => [],
	// },
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 20), "yyyy-MM-dd");

let positions = ref([]);

onMounted(async () => {
	const response = await axios.get(route("position.list"));
	positions.value = response.data;
});

const submitHandler = (data, node) => {
	Inertia.post(route("position.store"), data, {
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
const positionPositions = ref([]);
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add position</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<!-- <FormKit type="hidden" id="position" name="position" :value="position" /> -->
			<!-- <FormKit
				v-model="positionPositions"
				type="checkbox"
				name="positions"
				id="positions"
				validation="required|integer|min:1|max:2000"
				label="New role"
				placeholder="Select new Rank"
				:options="positions"
				error-visibility="submit"
			/> -->
			<FormKit
				id="name"
				type="text"
				name="name"
				validation="required|string"
				label="Name of position"
			/>
		</FormKit>
	</main>
</template>
