<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onBeforeMount, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	job: {
		type: Object,
		required: true,
	},
	types: {
		type: Array,
		default: () => [],
	},
	institution: {
		type: Object,
		default: () => null,
	},
});

import { format, addDays, subYears } from "date-fns";

let jobs = ref([]);
let categories = ref([]);

onBeforeMount(async () => {
	const response = await axios.get(route("job.create"));
	jobs.value = response.data;

	const response2 = await axios.get(route("job-category.create"));
	categories.value = response2.data;
});

const submitHandler = (data, node) => {
	Inertia.patch(route("job.update", { job: props.job.id }), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Rank</h1>
		<FormKit
			:value="job"
			@submit="submitHandler"
			type="form"
			submit-label="Save"
		>
			<FormKit
				type="text"
				name="name"
				id="name"
				label="Rank name"
				validation="required|string|length:2,150"
				validation-visibility="submit"
			/>
			<FormKit
				type="hidden"
				name="institution_id"
				id="institution_id"
				label="institution_id"
				:value="1"
				validation="required|integer|min:1|max:150"
				validation-visibility="submit"
				disabled
			/>
			<!-- <FormKit
        type="text"
        name="institution"
        id="institution"
        label="Institution"

        validation="required|string|length:1,150"
        validation-visibility="submit"
        disabled /> -->

			<FormKit
				type="select"
				name="job_category_id"
				id="job_category_id"
				validation="number|min:1|max:30"
				label="Harmonized grade"
				:value="job.category?.id"
				placeholder="Select harmonized grade"
				:options="categories"
				error-visibility="submit"
			/>
		</FormKit>
	</main>
</template>
