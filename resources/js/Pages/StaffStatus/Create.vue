<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import StaffStatusForm from "./partials/StaffStatusForm.vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	staff: Object,
	institution: Number,
});

let statuses = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.statuses", { institution: props.institution }),
	);
	statuses.value = response.data;
});

const submitHandler = (data, node) => {
	router.post(route("staff-status.store", { staff: data.staff_id }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["Error Submitting form"], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Change Status</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit type="hidden" name="staff_id" :value="staff.id" />
			<FormKit type="hidden" name="institution_id" :value="institution" />
			<StaffStatusForm :institution="institution" />
		</FormKit>
	</main>
</template>
