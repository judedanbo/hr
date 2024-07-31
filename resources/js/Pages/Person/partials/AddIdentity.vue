<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";
import IdentityForm from "./IdentityForm.vue";

const emit = defineEmits(["formSubmitted"]);

let props = defineProps({
	person: Number,
});

const submitHandler = (data, node) => {
	Inertia.post(
		route("person.identity.create", { person: props.person }),
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add Identification</h1>
		<FormKit @submit="submitHandler" type="form" submit-label="Save">
			<FormKit
				type="hidden"
				name="person_id"
				id="person_id"
				:value="props.person"
			/>
			<IdentityForm />
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
