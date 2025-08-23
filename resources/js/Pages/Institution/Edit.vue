<script setup>
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["formSubmitted"]);
import { format, addDays, subYears } from "date-fns";

let props = defineProps({
	selectedModel: Object,
});

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 5), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	router.patch(
		route("institution.update"),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors(["errors"], errors);
			},
		},
		props.selectedModel,
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Institution</h1>
		<FormKit
			type="form"
			submit-label="Save Institution"
			@submit="submitHandler"
		>
			<div class="sm:flex gap-4 flex-row">
				<div>
					<FormKit
						id="id"
						type="text"
						name="id"
						:value="selectedModel.id"
						validation="required|number|between:2,120"
						label="Institution id"
						placeholder="institution id"
						error-visibility="submit"
						:disabled="true"
					/>
				</div>
				<FormKit
					id="name"
					type="text"
					name="name"
					:value="selectedModel.name"
					validation="required|length:2,120"
					label="Institution name"
					placeholder="institution name"
					error-visibility="submit"
				/>
			</div>
			<div class="sm:flex gap-4">
				<FormKit
					id="abbreviation"
					type="text"
					name="abbreviation"
					:value="selectedModel.abbreviation"
					validation="length:2,6"
					label="Institution abbreviation"
					placeholder="institution's abbreviation"
					error-visibility="submit"
				/>
				<FormKit
					id="status"
					type="text"
					name="status"
					:value="selectedModel.status"
					validation="length:2,6"
					label="Status"
					placeholder="status"
					error-visibility="submit"
				/>
			</div>
			<div class="sm:flex gap-4">
				<FormKit
					id="start_date"
					type="date"
					name="start_date"
					:value="selectedModel.start_date"
					:min="end_date"
					:max="start_date"
					label="Start date"
					:validation="
						'required|date_after:' + end_date + '|date_before:' + start_date
					"
					validation-visibility="submit"
				/>
				<FormKit
					id="end_date"
					type="date"
					name="end_date"
					:value="selectedModel.end_date"
					:min="start_date"
					label="End date"
					validation-visibility="submit"
				/>
			</div>
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
