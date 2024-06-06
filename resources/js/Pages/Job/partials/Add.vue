<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onBeforeMount, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

defineProps({
	// jobs: Array,
	types: Array,
	institution: Object,
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

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	Inertia.post(route("job.store"), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add Rank</h1>
		<FormKit @submit="submitHandler" type="form" submit-label="Save">
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
				placeholder="Select harmonized grade"
				:options="categories"
				error-visibility="submit"
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
