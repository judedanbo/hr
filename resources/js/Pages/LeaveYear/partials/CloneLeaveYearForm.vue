<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import axios from "axios";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	leaveYear: { type: Object, required: true },
});

const years = ref([]);

const sourceOptions = computed(() =>
	years.value.filter((year) => year.value !== props.leaveYear.id),
);

onMounted(async () => {
	const response = await axios.get(route("leave-year.list"));
	years.value = response.data;
});

const submitHandler = (data, node) => {
	router.post(
		route("leave-year.clone", { leaveYear: props.leaveYear.id }),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => node.setErrors([], errors),
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-1 dark:text-gray-100">
			Clone configuration into {{ leaveYear.year }}
		</h1>
		<p class="text-sm text-gray-500 dark:text-gray-300 pb-4">
			Copies entitlements and recurring holidays from the selected year.
			Existing rows for {{ leaveYear.year }} are left untouched.
		</p>
		<FormKit type="form" submit-label="Clone" @submit="submitHandler">
			<FormKit
				type="select"
				name="source_leave_year_id"
				label="Copy configuration from"
				placeholder="Select source year"
				:options="sourceOptions"
				validation="required"
			/>
		</FormKit>
	</main>
</template>
