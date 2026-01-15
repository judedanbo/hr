<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import axios from "axios";
import { ArrowPathIcon } from "@heroicons/vue/20/solid";
import QualificationEvidence from "./partials/QualificationEvidence.vue";

const emit = defineEmits(["formSubmitted", "close"]);

const props = defineProps({
	qualification: {
		type: Object,
		required: true,
	},
});

const document = ref(null);
const documentTypes = ref([]);
const isSubmitting = ref(false);

onMounted(async () => {
	document.value = props.qualification.documents
		? props.qualification.documents[0]
		: null;

	const { data } = await axios.get(route("document-types"));
	documentTypes.value = data;
});

const submitHandler = (data, node) => {
	// Check if file is selected
	if (!data.file_name || data.file_name.length === 0) {
		node.setErrors(["Please select a file to upload"]);
		return;
	}

	isSubmitting.value = true;

	const formData = new FormData();
	formData.append("file_name", data.file_name[0].file);
	formData.append("document_type", data.document_type);
	formData.append(
		"document_title",
		data.document_title ?? data.file_name[0].name.split(".")[0],
	);
	formData.append("document_status", "P");
	formData.append("file_type", data.file_name[0].file.type);

	router.post(
		route("qualification-document.update", {
			qualification: props.qualification.id,
		}),
		formData,
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("formSubmitted");
			},
			onError: (errors) => {
				const errorMsg = {
					document_type: errors.document_type ?? "",
					document_title: errors.document_title ?? "",
					file_name: errors.file_name ?? "",
				};
				node.setErrors(["Please fix the errors below"], errorMsg);
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
		<div class="flex justify-between items-center pb-4">
			<h1 class="text-2xl dark:text-gray-100">Attach Document</h1>
			<button
				type="button"
				class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
				@click="emit('close')"
			>
				<span class="sr-only">Close</span>
				<svg
					class="h-6 w-6"
					fill="none"
					viewBox="0 0 24 24"
					stroke-width="1.5"
					stroke="currentColor"
				>
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						d="M6 18L18 6M6 6l12 12"
					/>
				</svg>
			</button>
		</div>

		<div class="mb-4 p-3 bg-gray-200 dark:bg-gray-600 rounded-md">
			<p class="text-sm text-gray-700 dark:text-gray-200">
				<span class="font-medium">Qualification:</span>
				{{ qualification.qualification || qualification.course }}
			</p>
			<p class="text-sm text-gray-500 dark:text-gray-300">
				{{ qualification.institution }} ({{ qualification.year }})
			</p>
		</div>

		<FormKit
			type="form"
			id="attachDocument"
			:disabled="isSubmitting"
			@submit="submitHandler"
		>
			<QualificationEvidence
				:document="document"
				:document-types="documentTypes"
			/>

			<template #submit>
				<div class="flex justify-end gap-3 mt-4">
					<button
						type="button"
						class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-sm text-gray-700 dark:text-gray-200 tracking-widest hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
						@click="emit('close')"
					>
						Cancel
					</button>
					<button
						type="submit"
						:disabled="isSubmitting"
						class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
					>
						<ArrowPathIcon
							v-if="isSubmitting"
							class="w-4 h-4 mr-2 animate-spin"
						/>
						{{ isSubmitting ? "Uploading..." : "Upload Document" }}
					</button>
				</div>
			</template>
		</FormKit>
	</main>
</template>
