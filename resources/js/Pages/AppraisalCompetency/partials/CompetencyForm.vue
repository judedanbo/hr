<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	competency: { type: Object, default: null },
	groups: { type: Array, default: () => [] },
	jobCategories: { type: Array, default: () => [] },
});

const isEdit = !!props.competency;

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
		router.patch(
			route("appraisal-competency.update", { appraisalCompetency: props.competency.id }),
			data,
			options,
		);
	} else {
		router.post(route("appraisal-competency.store"), data, options);
	}
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">
			{{ isEdit ? "Edit competency" : "Add competency" }}
		</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit type="text" name="name" validation="required|string" label="Competency name" :value="competency?.name" />
			<FormKit type="textarea" name="description" label="Description" :value="competency?.description" />
			<FormKit type="select" name="group" validation="required" label="Group" :options="groups" :value="competency?.group ?? 'core'" />
			<FormKit type="number" name="default_weight" validation="required|number|between:0,100" label="Default weight (%)" :value="competency?.default_weight ?? 10" />
			<FormKit
				type="select"
				name="job_category_id"
				label="Job category (optional)"
				:options="[{ value: '', label: 'All categories' }, ...jobCategories]"
				:value="competency?.job_category_id ?? ''"
			/>
			<FormKit type="checkbox" name="is_active" label="Active" :value="competency ? competency.is_active : true" />
		</FormKit>
	</main>
</template>
