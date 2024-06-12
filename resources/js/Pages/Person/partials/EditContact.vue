<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";
import ContactForm from "@/Pages/Person/partials/ContactForm.vue";

const emit = defineEmits(["formSubmitted"]);

let props = defineProps({
	contact: {
		type: Object,
		required: true,
	},
	person: {
		type: Number,
		required: true,
	},
});

const contact_types = ref([]);
onMounted(async () => {
	const { data } = await axios.get(route("contact-type.index"));
	contact_types.value = data;
});

const submitHandler = (data, node) => {
	console.log(props.contact.id);
	Inertia.post(
		route("person.contact.update", {
			person: props.person,
			contact: props.contact.id,
		}),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors([""], errors);
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Contacts</h1>
		{{ props.contact }}
		<FormKit
			:value="props.contact"
			type="form"
			submit-label="Save"
			@submit="submitHandler"
		>
			<FormKit type="hidden" name="id" :value="props.contact.id" />
			<ContactForm />
		</FormKit>
	</main>
</template>
