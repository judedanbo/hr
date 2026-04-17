<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import {
	ArrowPathIcon,
	XMarkIcon,
	DocumentIcon,
} from "@heroicons/vue/24/outline";

const emit = defineEmits(["formSubmitted", "close"]);

const props = defineProps({
	person: { type: Number, required: true },
});

const qualificationLevels = ref([]);
const documentTypes = ref([]);
const isSubmitting = ref(false);
const selectedFile = ref(null);
const previewUrl = ref(null);
const previewFileType = ref(null);

onMounted(async () => {
	const [levelsRes, typesRes] = await Promise.all([
		axios.get(route("qualification-level.index")),
		axios.get(route("document-types")),
	]);
	qualificationLevels.value = levelsRes.data;
	documentTypes.value = typesRes.data;
});

function onFileInput(files) {
	if (files && files.length > 0) {
		const f = files[0].file;
		selectedFile.value = f;
		previewUrl.value = URL.createObjectURL(f);
		previewFileType.value = f.type;
	} else {
		selectedFile.value = null;
		previewUrl.value = null;
		previewFileType.value = null;
	}
}

function submitHandler(data, node) {
	isSubmitting.value = true;

	const formData = new FormData();
	formData.append("person_id", String(props.person));
	formData.append("course", data.course ?? "");
	formData.append("level", data.level ?? "");
	formData.append("qualification", data.qualification ?? "");
	formData.append("qualification_number", data.qualification_number ?? "");
	formData.append("institution", data.institution ?? "");
	formData.append("year", data.year ?? "");

	if (data.file_name && data.file_name.length > 0) {
		formData.append("file_name", data.file_name[0].file);
		formData.append("document_type", data.document_type ?? "");
		if (data.document_title) {
			formData.append("document_title", data.document_title);
		}
	}

	router.post(route("qualification.store"), formData, {
		forceFormData: true,
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			selectedFile.value = null;
			previewUrl.value = null;
			previewFileType.value = null;
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors([""], errors);
		},
		onFinish: () => {
			isSubmitting.value = false;
		},
	});
}
</script>

<template>
	<main
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 sm:p-6 shadow-sm"
	>
		<!-- Header -->
		<header class="flex justify-between items-start mb-5">
			<div>
				<h2 class="text-lg font-bold text-gray-900 dark:text-gray-50">
					Add Qualification
				</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
					Degree, diploma, certificate, or training. Optional certificate
					upload.
				</p>
			</div>
			<button
				type="button"
				class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
				@click="emit('close')"
			>
				<XMarkIcon class="h-5 w-5" />
				<span class="sr-only">Close</span>
			</button>
		</header>

		<FormKit
			type="form"
			:disabled="isSubmitting"
			:actions="false"
			@submit="submitHandler"
		>
			<FormKit type="hidden" name="person_id" :value="props.person" />

			<!-- Qualification Details section -->
			<div>
				<h3
					class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3"
				>
					Qualification details
				</h3>
				<div class="sm:grid sm:grid-cols-2 sm:gap-x-4">
					<FormKit
						id="course"
						type="text"
						name="course"
						label="Course"
						placeholder="e.g. BSc Accounting"
						validation="required|length:2,100"
						validation-visibility="submit"
					/>
					<FormKit
						id="level"
						type="select"
						name="level"
						label="Level"
						placeholder="Select level"
						:options="
							qualificationLevels.map((l) => ({
								label: l.label,
								value: l.value,
							}))
						"
					/>
					<FormKit
						id="qualification"
						type="text"
						name="qualification"
						label="Qualification name"
						placeholder="e.g. Bachelor of Science"
						validation="length:0,100"
						validation-visibility="submit"
					/>
					<FormKit
						id="qualification_number"
						type="text"
						name="qualification_number"
						label="Qualification number"
						placeholder="Certificate ID"
						validation="length:0,10"
						validation-visibility="submit"
					/>
					<FormKit
						id="institution"
						type="text"
						name="institution"
						label="Institution"
						placeholder="e.g. University of Ghana"
						validation="length:0,100"
						validation-visibility="submit"
					/>
					<FormKit
						id="year"
						type="text"
						name="year"
						label="Year of graduation"
						placeholder="e.g. 2015"
						validation="length:0,4|matches:/^[0-9]*$/"
						validation-visibility="submit"
					/>
				</div>
			</div>

			<!-- Supporting Document section -->
			<div
				class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700"
			>
				<div class="flex items-baseline justify-between mb-3">
					<h3
						class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Supporting document
					</h3>
					<span
						class="text-[11px] italic text-gray-500 dark:text-gray-400"
						>Optional — you can add or change a document later.</span
					>
				</div>

				<div class="sm:grid sm:grid-cols-2 sm:gap-x-4">
					<FormKit
						id="document_type"
						type="select"
						name="document_type"
						label="Document type"
						placeholder="Select type"
						:options="documentTypes"
					/>
					<FormKit
						id="document_title"
						type="text"
						name="document_title"
						label="Document title"
						placeholder="Auto-filled from filename if blank"
						validation="length:0,100"
					/>
				</div>

				<FormKit
					id="file_name"
					type="file"
					name="file_name"
					label="File"
					accept=".pdf,.jpg,.jpeg,.png"
					help="Accepted formats: PDF, JPG, PNG (max 2 MB)."
					@input="onFileInput"
				/>

				<!-- Inline file preview -->
				<div
					v-if="selectedFile"
					class="mt-2 flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-2.5"
				>
					<div
						class="w-10 h-10 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0"
					>
						<img
							v-if="previewFileType?.startsWith('image/')"
							:src="previewUrl"
							alt=""
							class="w-full h-full object-cover"
						/>
						<DocumentIcon
							v-else
							class="h-5 w-5 text-gray-400"
						/>
					</div>
					<div class="flex-1 min-w-0 text-xs">
						<p
							class="font-semibold text-gray-900 dark:text-gray-100 truncate"
						>
							{{ selectedFile.name }}
						</p>
						<p class="text-gray-500 dark:text-gray-400">
							{{ (selectedFile.size / 1024).toFixed(0) }} KB &middot;
							{{ selectedFile.type || "unknown type" }}
						</p>
					</div>
				</div>
			</div>

			<!-- Actions -->
			<div
				class="flex justify-end gap-2 mt-6 pt-4 border-t border-gray-100 dark:border-gray-700"
			>
				<button
					type="button"
					class="text-sm font-semibold text-gray-600 dark:text-gray-300 hover:underline px-3 py-2 transition-colors"
					@click="emit('close')"
				>
					Cancel
				</button>
				<button
					type="submit"
					:disabled="isSubmitting"
					class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
				>
					<ArrowPathIcon
						v-if="isSubmitting"
						class="w-4 h-4 animate-spin"
					/>
					{{ isSubmitting ? "Saving..." : "Save qualification" }}
				</button>
			</div>
		</FormKit>
	</main>
</template>
