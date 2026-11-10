<script setup>
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	position: { type: Object, required: true },
});

const submitHandler = (data, node) => {
	router.patch(
		route("position.update", { position: props.position.id }),
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit position</h1>
		<FormKit
			type="form"
			submit-label="Save"
			:value="{ name: position.name }"
			@submit="submitHandler"
		>
			<FormKit
				id="name"
				type="text"
				name="name"
				validation="required|string"
				label="Name of position"
			/>
		</FormKit>
	</main>
</template>
