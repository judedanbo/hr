<script setup>
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["formSubmitted"]);

import { format, addDays, subYears } from "date-fns";

defineProps({
	person: Number,
});

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
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add Qualification</h1>
		<FormKit type="form" submit-label="Create" @submit="submitHandler">
			<FormKit id="person_id" type="hidden" name="person_id" :value="person" />
			<FormKit
				id="institution"
				type="text"
				name="institution"
				label="Institution"
				validation="string|length:2,100"
				validation-visibility="submit"
			/>
			<div class="sm:flex gap-4">
				<FormKit
					id="course"
					type="text"
					name="course"
					label="Course"
					validation="required|string|length:2,100"
					validation-visibility="submit"
				/>
				<div>
					<FormKit
						id="level"
						type="text"
						name="level"
						label="Level"
						validation="string|length:2,100"
						validation-visibility="submit"
					/>
				</div>
			</div>
			<div class="sm:flex gap-4">
				<FormKit
					id="qualification"
					type="text"
					name="qualification"
					label="Qualification"
					validation="string|length:2,100"
					validation-visibility="submit"
				/>
				<div>
					<FormKit
						id="qualification_number"
						type="text"
						name="qualification_number"
						label="Qualification Number"
						validation="string|length:2,100"
						validation-visibility="submit"
					/>
				</div>
			</div>
			<div class="w-1/2 sm:w-1/3 xl:w-1/4">
				<FormKit
					id="year"
					type="text"
					name="year"
					label="Year of Graduation"
					validation="string|length:2,100"
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
