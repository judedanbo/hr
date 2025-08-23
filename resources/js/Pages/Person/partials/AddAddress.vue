<script setup>
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["formSubmitted"]);
import AddressForm from "@/Pages/Person/partials/AddressForm.vue";

import { format, addDays, subYears } from "date-fns";

let props = defineProps({
	qualifications: Array,
	person: Number,
});
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	router.post(route("person.address.create", { person: props.person }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["errors"], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add Address</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit id="person_id" type="hidden" name="person_id" :value="person" />
			<AddressForm />
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
