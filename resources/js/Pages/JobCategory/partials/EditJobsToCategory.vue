<script setup>
import { onMounted, ref } from "vue";
import { format, addDays, subYears } from "date-fns";
import { Inertia } from "@inertiajs/inertia";

const values = ref([]);

let jobs = ref([]);
onMounted(async () => {
	const response = await axios.get(route("job.create"));
	jobs.value = response.data;
});

const props = defineProps({
	category: {
		type: Object,
		required: true,
	},
});
let categories = ref([]);
// let institution = ref([]);
onMounted(async () => {
	const response = await axios.get(route("job-category.create"));
	categories.value = response.data;
});

const emit = defineEmits(["formSubmitted"]);
const submitHandler = (data, node) => {
	Inertia.patch(
		route("job-category.update", { jobCategory: props.category.id }),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors(["Error on submission"], errors);
			},
		},
	);
};
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");
</script>
<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Harmonized Grade</h1>
		<FormKit
			@submit="submitHandler"
			type="form"
			:value="category"
			submit-label="Save"
		>
			<FormKit
				type="text"
				name="name"
				id="name"
				label="Category name"
				validation="required|string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				type="hidden"
				name="institution_id"
				id="institution_id"
				validation-visibility="submit"
			/>
			<FormKit
				type="text"
				name="short_name"
				id="short_name"
				label="Abbreviation"
				validation="required|string|length:2,10"
				validation-visibility="submit"
			/>
			<FormKit
				type="number"
				name="level"
				id="level"
				label="Level"
				min="1"
				max="50"
				validation="number|min:1|max:100"
				validation-visibility="submit"
			/>

			<FormKit
				type="select"
				name="job_category_id"
				id="job_category_id"
				placeholder="Select parent category"
				validation="number|min:1|max:100"
				label="Next Level"
				:options="categories"
				error-visibility="submit"
			/>

			<div class="sm:flex gap-4">
				<FormKit
					type="date"
					name="start_date"
					id="start_date"
					:min="end_date"
					:max="start_date"
					label="Start date"
					:validation="'date_after:' + end_date + '|date_before:' + start_date"
					validation-visibility="submit"
				/>
			</div>
		</FormKit>
	</main>
</template>
