<script setup>
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["formSubmitted"]);
import StaffPositionForm from "@/Pages/StaffPosition/partials/StaffPositionForm.vue";

const props = defineProps({
	staff: { type: Object, required: true },
	institution: { type: Number, required: true },
});

const submitHandler = (data, node) => {
	router.post(route("staff.position.store", { staff: props.staff.id }), data, {
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
		<h1 class="text-2xl pb-4 dark:text-gray-100">Change Staff Position</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<StaffPositionForm :institution="institution" />
		</FormKit>
	</main>
</template>
