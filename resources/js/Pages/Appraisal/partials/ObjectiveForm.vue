<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	appraisalId: { type: Number, required: true },
	objective: { type: Object, default: null },
});

const isEdit = !!props.objective;

const submitHandler = (data, node) => {
	const options = {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors([], errors);
		},
	};

	if (isEdit) {
		router.patch(route("appraisal.objective.update", { appraisal: props.appraisalId, objective: props.objective.id }), data, options);
	} else {
		router.post(route("appraisal.objective.store", { appraisal: props.appraisalId }), data, options);
	}
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">{{ isEdit ? "Edit objective" : "Add objective" }}</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit type="text" name="title" validation="required|string" label="Objective" :value="objective?.title" />
			<FormKit type="textarea" name="description" label="Description" :value="objective?.description" />
			<FormKit type="text" name="measure" label="Measure / target" :value="objective?.measure" />
			<FormKit type="number" name="weight" validation="required|number|between:0,100" label="Weight (%)" :value="objective?.weight ?? 0" />
		</FormKit>
	</main>
</template>
