<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);
import StaffPositionForm from "@/Pages/StaffPosition/partials/StaffPositionForm.vue";

const props = defineProps({
	staff: { type: Object, required: true },
	institution: { type: Number, required: true },
});

let positions = ref([]);

onMounted(async () => {
	const response = await axios.get(route("position.store"));
	positions.value = response.data;
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
			<FormKit type="hidden" name="staff_id" :value="staff.id" />
			<FormKit type="hidden" name="institution_id" :value="institution" />
			<StaffPositionForm :institution="institution" />
		</FormKit>
	</main>
</template>
