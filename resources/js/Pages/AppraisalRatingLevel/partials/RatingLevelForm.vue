<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	level: { type: Object, default: null },
});

const isEdit = !!props.level;

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
			route("appraisal-rating-level.update", { appraisalRatingLevel: props.level.id }),
			data,
			options,
		);
	} else {
		router.post(route("appraisal-rating-level.store"), data, options);
	}
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">
			{{ isEdit ? "Edit rating level" : "Add rating level" }}
		</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit type="number" name="value" validation="required|number" label="Value" :value="level?.value" />
			<FormKit type="text" name="label" validation="required|string" label="Label" :value="level?.label" />
			<div class="grid grid-cols-2 gap-x-4">
				<FormKit type="number" step="0.01" name="min_score" validation="required|number" label="Min score" :value="level?.min_score" />
				<FormKit type="number" step="0.01" name="max_score" validation="required|number" label="Max score" :value="level?.max_score" />
			</div>
			<FormKit type="text" name="description" label="Description" :value="level?.description" />
			<FormKit type="text" name="color" label="Tailwind color class (optional)" :value="level?.color" />
		</FormKit>
	</main>
</template>
