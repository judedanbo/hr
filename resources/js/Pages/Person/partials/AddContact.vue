<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";
import ContactForm from "@/Pages/Person/partials/ContactForm.vue";

const emit = defineEmits(["formSubmitted"]);

import { format, addDays, subYears } from "date-fns";

let props = defineProps({
	person: Number,
});

const contact_types = ref([]);
onMounted(async () => {
	const { data } = await axios.get(route("contact-type.index"));
	contact_types.value = data;
});
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
	Inertia.post(route("person.contact.create", { person: props.person }), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add Contacts</h1>
		<FormKit @submit="submitHandler" type="form" submit-label="Save">
			<FormKit
				type="hidden"
				name="person_id"
				id="person_id"
				:value="props.person"
			/>
			<ContactForm />
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
