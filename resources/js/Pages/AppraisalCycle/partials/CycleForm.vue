<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	cycle: { type: Object, default: null },
	statuses: { type: Array, default: () => [] },
});

const isEdit = !!props.cycle;

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
			route("appraisal-cycle.update", { appraisalCycle: props.cycle.id }),
			data,
			options,
		);
	} else {
		router.post(route("appraisal-cycle.store"), data, options);
	}
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">
			{{ isEdit ? "Edit appraisal cycle" : "Add appraisal cycle" }}
		</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit
				type="text"
				name="name"
				validation="required|string"
				label="Cycle name"
				:value="cycle?.name"
			/>
			<FormKit
				type="number"
				name="year"
				validation="required|number"
				label="Year"
				:value="cycle?.year"
			/>
			<div class="grid grid-cols-2 gap-x-4">
				<FormKit type="date" name="objective_window_start" label="Objective window start" :value="cycle?.objective_window_start" />
				<FormKit type="date" name="objective_window_end" label="Objective window end" :value="cycle?.objective_window_end" />
				<FormKit type="date" name="midyear_window_start" label="Mid-year window start" :value="cycle?.midyear_window_start" />
				<FormKit type="date" name="midyear_window_end" label="Mid-year window end" :value="cycle?.midyear_window_end" />
				<FormKit type="date" name="final_window_start" label="Final window start" :value="cycle?.final_window_start" />
				<FormKit type="date" name="final_window_end" label="Final window end" :value="cycle?.final_window_end" />
			</div>
			<div class="grid grid-cols-2 gap-x-4">
				<FormKit
					type="number"
					name="objectives_weight"
					validation="required|number|between:0,100"
					label="Objectives weight (%)"
					:value="cycle?.objectives_weight ?? 70"
				/>
				<FormKit
					type="number"
					name="competencies_weight"
					validation="required|number|between:0,100"
					label="Competencies weight (%)"
					:value="cycle?.competencies_weight ?? 30"
				/>
			</div>
			<FormKit
				type="select"
				name="status"
				label="Status"
				:options="statuses"
				:value="cycle?.status ?? 'draft'"
			/>
		</FormKit>
	</main>
</template>
