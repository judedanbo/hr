<script setup>
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import {
	ArrowPathIcon,
	XMarkIcon,
	DocumentIcon,
	XCircleIcon,
} from "@heroicons/vue/24/outline";

const emit = defineEmits(["formSubmitted", "close"]);

const props = defineProps({
	qualification: { type: Object, required: true },
});

const documentTypes = ref([]);
const isSubmitting = ref(false);

onMounted(async () => {
	const { data } = await axios.get(route("document-types"));
	documentTypes.value = data;
});

// selectedFiles: [{ file, document_type, document_title, url, preview_type }]
const selectedFiles = ref([]);

function titleFromFilename(name) {
	return String(name).replace(/\.[^.]+$/, "");
}

function onFileInput(event) {
	const files = Array.from(event.target.files || []);
	const entries = files.map((f) => ({
		file: f,
		document_type: "",
		document_title: titleFromFilename(f.name),
		url: URL.createObjectURL(f),
		preview_type: f.type,
	}));
	selectedFiles.value = [...selectedFiles.value, ...entries];
	event.target.value = "";
}

function removeFile(index) {
	const entry = selectedFiles.value[index];
	if (entry?.url) {
		URL.revokeObjectURL(entry.url);
	}
	selectedFiles.value = selectedFiles.value.filter((_, i) => i !== index);
}

function formatSize(bytes) {
	return (bytes / 1024).toFixed(0) + " KB";
}

const hasIncompleteFiles = computed(() =>
	selectedFiles.value.some(
		(e) => !e.document_type || !(e.document_title ?? "").trim(),
	),
);

const saveLabel = computed(() => {
	if (isSubmitting.value) {
		return "Saving...";
	}
	const n = selectedFiles.value.length;
	if (n === 0) {
		return "Attach documents";
	}
	return `Attach ${n} document${n === 1 ? "" : "s"}`;
});

const serverErrors = ref({});

function submit() {
	if (selectedFiles.value.length === 0 || hasIncompleteFiles.value) {
		return;
	}

	serverErrors.value = {};
	isSubmitting.value = true;

	const fd = new FormData();
	selectedFiles.value.forEach((entry) => {
		fd.append("file_name[]", entry.file);
		fd.append("document_type[]", entry.document_type);
		fd.append("document_title[]", entry.document_title);
	});

	router.post(
		route("qualification-document.store", {
			qualification: props.qualification.id,
		}),
		fd,
		{
			forceFormData: true,
			preserveScroll: true,
			onSuccess: () => {
				selectedFiles.value.forEach((e) => e.url && URL.revokeObjectURL(e.url));
				selectedFiles.value = [];
				emit("formSubmitted");
			},
			onError: (errors) => {
				serverErrors.value = errors;
			},
			onFinish: () => {
				isSubmitting.value = false;
			},
		},
	);
}
</script>

<template>
	<main
		class="bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 shadow-sm max-w-2xl"
	>
		<header class="flex justify-between items-start mb-5">
			<div>
				<h2 class="text-lg font-bold text-gray-900 dark:text-gray-50">
					Attach documents
				</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
					{{ qualification.qualification || qualification.course }}
					<span v-if="qualification.institution">
						— {{ qualification.institution }}</span
					>
					<span v-if="qualification.year">, {{ qualification.year }}</span>
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

		<!-- Server errors summary -->
		<div
			v-if="Object.keys(serverErrors).length"
			class="mb-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-xs text-red-700 dark:text-red-400 space-y-1"
		>
			<p v-for="(msg, key) in serverErrors" :key="key">{{ msg }}</p>
		</div>

		<!-- File picker -->
		<div class="mb-3">
			<label
				class="inline-flex items-center gap-2 cursor-pointer rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/30 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:border-emerald-400 transition-colors px-4 py-3 text-sm text-gray-600 dark:text-gray-400"
			>
				<DocumentIcon class="h-4 w-4 text-gray-400" />
				Choose files (PDF, JPG, PNG — max 2 MB each)
				<input
					type="file"
					multiple
					accept=".pdf,.jpg,.jpeg,.png"
					class="sr-only"
					@change="onFileInput"
				/>
			</label>
		</div>

		<!-- Per-file rows with preview -->
		<ul v-if="selectedFiles.length > 0" class="space-y-3 mb-4">
			<li
				v-for="(entry, index) in selectedFiles"
				:key="index"
				class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3"
			>
				<div class="flex items-start gap-3">
					<!-- Preview -->
					<div
						class="w-16 h-16 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0"
					>
						<img
							v-if="entry.preview_type?.startsWith('image/')"
							:src="entry.url"
							alt=""
							class="w-full h-full object-cover"
						/>
						<DocumentIcon v-else class="h-6 w-6 text-gray-400" />
					</div>

					<!-- Metadata -->
					<div class="flex-1 min-w-0 space-y-2">
						<div>
							<p
								class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate"
							>
								{{ entry.file.name }}
							</p>
							<p class="text-[11px] text-gray-500 dark:text-gray-400">
								{{ formatSize(entry.file.size) }} ·
								{{ entry.file.type || "unknown type" }}
							</p>
						</div>
						<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
							<select
								v-model="entry.document_type"
								class="rounded border border-gray-300 bg-white text-gray-900 placeholder-gray-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 text-sm py-1 px-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:focus:ring-emerald-400 dark:focus:border-emerald-400"
								:class="{
									'border-red-400 dark:border-red-500': !entry.document_type,
								}"
							>
								<option
									value=""
									class="text-gray-900 dark:bg-gray-900 dark:text-gray-100"
								>
									Select type
								</option>
								<option
									v-for="t in documentTypes"
									:key="t.value"
									:value="t.value"
									class="text-gray-900 dark:bg-gray-900 dark:text-gray-100"
								>
									{{ t.label }}
								</option>
							</select>
							<input
								v-model.trim="entry.document_title"
								type="text"
								placeholder="Title"
								class="rounded border border-gray-300 bg-white text-gray-900 placeholder-gray-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 text-sm py-1 px-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:focus:ring-emerald-400 dark:focus:border-emerald-400"
								:class="{
									'border-red-400 dark:border-red-500': !entry.document_title,
								}"
							/>
						</div>
					</div>

					<!-- Remove -->
					<button
						type="button"
						class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors flex-shrink-0"
						:aria-label="`Remove ${entry.file.name}`"
						@click="removeFile(index)"
					>
						<XCircleIcon class="h-5 w-5" />
					</button>
				</div>
			</li>
		</ul>

		<div
			v-if="hasIncompleteFiles"
			class="mb-3 text-[11px] text-amber-700 dark:text-amber-300"
		>
			Each file needs a document type and title before you can save.
		</div>

		<!-- Footer -->
		<div
			class="flex justify-end gap-2 mt-6 pt-4 border-t border-gray-100 dark:border-gray-700"
		>
			<button
				type="button"
				class="text-sm font-semibold text-gray-600 dark:text-gray-300 hover:underline px-3 py-2 transition-colors"
				:disabled="isSubmitting"
				@click="emit('close')"
			>
				Cancel
			</button>
			<button
				type="button"
				:disabled="
					isSubmitting || selectedFiles.length === 0 || hasIncompleteFiles
				"
				class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
				@click="submit"
			>
				<ArrowPathIcon v-if="isSubmitting" class="w-4 h-4 animate-spin" />
				{{ saveLabel }}
			</button>
		</div>
	</main>
</template>
