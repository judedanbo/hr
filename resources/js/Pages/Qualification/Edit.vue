<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import axios from "axios";
import { ArrowPathIcon } from "@heroicons/vue/20/solid";
import QualificationForm from "./partials/QualificationForm.vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	qualification: {
		type: Object,
		required: true,
	},
});

const qualificationLevels = ref([]);
const isSubmitting = ref(false);

onMounted(async () => {
	const { data } = await axios.get(route("qualification-level.index"));
	qualificationLevels.value = data;
});

const submitHandler = (data, node) => {
	isSubmitting.value = true;
	router.patch(
		route("qualification.update", {
			qualification: props.qualification.id,
		}),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors(["Server side errors"], errors);
			},
			onFinish: () => {
				isSubmitting.value = false;
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Qualification</h1>

		<FormKit
			type="form"
			:disabled="isSubmitting"
			:value="qualification"
			@submit="submitHandler"
		>
			<QualificationForm :qualification-levels="qualificationLevels" />

			<template #submit>
				<button
					type="submit"
					:disabled="isSubmitting"
					class="mt-4 inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
				>
					<ArrowPathIcon
						v-if="isSubmitting"
						class="w-4 h-4 mr-2 animate-spin"
					/>
					{{ isSubmitting ? "Saving..." : "Save" }}
				</button>
			</template>
		</FormKit>
	</main>
</template>
