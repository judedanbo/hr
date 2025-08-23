<script setup>
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["formSubmitted"]);

import { format, addDays, subYears } from "date-fns";

defineProps({
	qualifications: Array,
	person: Number,
});
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	router.post(route("qualification.store"), data, {
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
</script>

<template>
	<main class="p-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add Qualification</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit id="person_id" type="hidden" name="person_id" :value="person" />
			<FormKit
				id="course"
				type="text"
				name="course"
				label="Course"
				validation="required|string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				id="institution"
				type="text"
				name="institution"
				label="Institution"
				validation="string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				id="qualification"
				type="text"
				name="qualification"
				label="Qualification"
				validation="string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				id="qualification_number"
				type="text"
				name="qualification_number"
				label="Qualification Number"
				validation="string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				id="level"
				type="text"
				name="level"
				label="Level"
				validation="string|length:2,100"
				validation-visibility="submit"
			/>
			<FormKit
				id="year"
				type="text"
				name="year"
				label="Year of Graduation"
				validation="string|length:2,100"
				validation-visibility="submit"
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
