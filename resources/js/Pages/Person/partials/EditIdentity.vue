<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import IdentityForm from "@/Pages/Person/partials/IdentityForm.vue";

const emit = defineEmits(["formSubmitted"]);

let props = defineProps({
	identity: {
		type: Object,
		required: true,
	},
	person: {
		type: Number,
		required: true,
	},
});

const identity_types = ref([]);
onMounted(async () => {
	const { data } = await axios.get(route("identity.index"));
	identity_types.value = data;
});

const submitHandler = (data, node) => {
	router.post(
		route("person.identity.update", {
			person: props.person,
			identity: props.identity.id,
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Identification</h1>
		<FormKit
			:value="identity"
			type="form"
			submit-label="Save"
			@submit="submitHandler"
		>
			<FormKit type="hidden" name="id" />
			<IdentityForm />
		</FormKit>
	</main>
</template>
