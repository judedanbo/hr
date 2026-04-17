<script setup>
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { getNode } from "@formkit/core";
import axios from "axios";
import {
	ArrowPathIcon,
	XMarkIcon,
	DocumentIcon,
	XCircleIcon,
} from "@heroicons/vue/24/outline";
import { CheckCircleIcon } from "@heroicons/vue/24/solid";

const emit = defineEmits(["formSubmitted", "close"]);

const props = defineProps({
	person: { type: Number, required: true },
});

// ── Options ──────────────────────────────────────────────────────────────────
const qualificationLevels = ref([]);
const documentTypes = ref([]);

onMounted(async () => {
	const [levelsRes, typesRes] = await Promise.all([
		axios.get(route("qualification-level.index")),
		axios.get(route("document-types")),
	]);
	qualificationLevels.value = levelsRes.data;
	documentTypes.value = typesRes.data;
});

// ── Wizard state ──────────────────────────────────────────────────────────────
const currentStep = ref(1);
const isSubmitting = ref(false);

// ── Shared form state (persists across steps) ─────────────────────────────────
const form = ref({
	course: "",
	level: "",
	qualification: "",
	qualification_number: "",
	institution: "",
	year: "",
});

// ── Step-1 validation errors ──────────────────────────────────────────────────
const step1Errors = ref({});

// ── Step-2: selected files with per-file metadata ────────────────────────────
// Each entry: { file: File, document_type: string, document_title: string }
const selectedFiles = ref([]);

function onFileInput(event) {
	const files = Array.from(event.target.files || []);
	const newEntries = files.map((file) => ({
		file,
		document_type: "",
		document_title: file.name.replace(/\.[^.]+$/, ""),
	}));
	selectedFiles.value = [...selectedFiles.value, ...newEntries];
	event.target.value = "";
}

function removeFile(index) {
	selectedFiles.value = selectedFiles.value.filter((_, i) => i !== index);
}

function formatSize(bytes) {
	return (bytes / 1024).toFixed(0) + " KB";
}

// ── Step 1 → 2 ───────────────────────────────────────────────────────────────
// FormKit only fires @submit after validation passes, so both "Next" and
// "Save without document" route through this single handler. The button
// sets `pendingAction` on click (before the submit fires) so we know which
// path to take once validation is done.
const pendingAction = ref("next");

function onStep1Submit() {
	step1Errors.value = {};
	if (pendingAction.value === "save") {
		selectedFiles.value = [];
		submit();
	} else {
		currentStep.value = 2;
	}
	pendingAction.value = "next";
}

function clickSaveWithoutDocuments() {
	pendingAction.value = "save";
	getNode("addQualificationStep1")?.submit();
}

function goBackToStep1() {
	currentStep.value = 1;
}

// ── Step-2 validation ─────────────────────────────────────────────────────────
const hasIncompleteFiles = computed(() =>
	selectedFiles.value.some(
		(entry) => !entry.document_type || !entry.document_title.trim(),
	),
);

// ── Save button label ─────────────────────────────────────────────────────────
const saveLabel = computed(() => {
	if (isSubmitting.value) {
		return "Saving...";
	}
	const n = selectedFiles.value.length;
	if (n === 0) {
		return "Save qualification";
	}
	return `Save with ${n} document${n === 1 ? "" : "s"}`;
});

// ── Submit ────────────────────────────────────────────────────────────────────
function buildFormData() {
	const fd = new FormData();
	fd.append("person_id", String(props.person));
	fd.append("course", form.value.course ?? "");
	fd.append("level", form.value.level ?? "");
	fd.append("qualification", form.value.qualification ?? "");
	fd.append(
		"qualification_number",
		form.value.qualification_number ?? "",
	);
	fd.append("institution", form.value.institution ?? "");
	fd.append("year", form.value.year ?? "");

	if (selectedFiles.value.length > 0) {
		selectedFiles.value.forEach((entry) => {
			fd.append("file_name[]", entry.file);
			fd.append("document_type[]", entry.document_type);
			fd.append("document_title[]", entry.document_title);
		});
	}

	return fd;
}

// Step-1 field names — used to route server errors back to step 1
const step1Fields = new Set([
	"person_id",
	"course",
	"level",
	"qualification",
	"qualification_number",
	"institution",
	"year",
]);

function handleServerErrors(errors) {
	const s1 = {};
	const s2 = {};
	Object.entries(errors).forEach(([key, message]) => {
		const baseKey = key.split(".")[0];
		if (step1Fields.has(baseKey)) {
			s1[key] = message;
		} else {
			s2[key] = message;
		}
	});

	// If any step-1 errors, go back and surface them
	if (Object.keys(s1).length > 0) {
		step1Errors.value = s1;
		currentStep.value = 1;
	}

	// Step-2 errors are surfaced via the serverErrors ref on step 2
	step2Errors.value = s2;
}

const step2Errors = ref({});

function submit() {
	isSubmitting.value = true;
	step2Errors.value = {};

	router.post(route("qualification.store"), buildFormData(), {
		forceFormData: true,
		preserveScroll: true,
		onSuccess: () => {
			// Reset everything
			form.value = {
				course: "",
				level: "",
				qualification: "",
				qualification_number: "",
				institution: "",
				year: "",
			};
			selectedFiles.value = [];
			currentStep.value = 1;
			step1Errors.value = {};
			step2Errors.value = {};
			emit("formSubmitted");
		},
		onError: handleServerErrors,
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
					Degree, diploma, certificate, or training. Optional
					certificate upload.
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

		<!-- Step indicator -->
		<div class="flex items-center gap-3 mb-6">
			<!-- Step 1 -->
			<div class="flex items-center gap-2">
				<div
					class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors"
					:class="
						currentStep === 1
							? 'bg-emerald-600 border-emerald-600 text-white'
							: 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-500 text-emerald-600 dark:text-emerald-400'
					"
				>
					<CheckCircleIcon
						v-if="currentStep === 2"
						class="w-4 h-4"
					/>
					<span v-else>1</span>
				</div>
				<span
					class="text-xs font-semibold"
					:class="
						currentStep === 1
							? 'text-emerald-700 dark:text-emerald-400'
							: 'text-gray-400 dark:text-gray-500'
					"
					>Details</span
				>
			</div>

			<!-- Connector -->
			<div
				class="flex-1 h-px transition-colors"
				:class="
					currentStep === 2
						? 'bg-emerald-400'
						: 'bg-gray-200 dark:bg-gray-700'
				"
			/>

			<!-- Step 2 -->
			<div class="flex items-center gap-2">
				<div
					class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors"
					:class="
						currentStep === 2
							? 'bg-emerald-600 border-emerald-600 text-white'
							: 'border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500'
					"
				>
					2
				</div>
				<span
					class="text-xs font-semibold"
					:class="
						currentStep === 2
							? 'text-emerald-700 dark:text-emerald-400'
							: 'text-gray-400 dark:text-gray-500'
					"
					>Documents</span
				>
			</div>
		</div>

		<!-- ── STEP 1: Qualification details ─────────────────────────────── -->
		<div v-show="currentStep === 1">
			<FormKit
				id="addQualificationStep1"
				type="form"
				:disabled="isSubmitting"
				:actions="false"
				@submit="onStep1Submit"
			>
				<h3
					class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3"
				>
					Qualification details
				</h3>

				<!-- Server errors for step-1 fields -->
				<div
					v-if="Object.keys(step1Errors).length"
					class="mb-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-xs text-red-700 dark:text-red-400 space-y-1"
				>
					<p v-for="(msg, key) in step1Errors" :key="key">
						{{ msg }}
					</p>
				</div>

				<!-- Row 1: Institution (full width) -->
				<FormKit
					id="institution"
					v-model="form.institution"
					type="text"
					name="institution"
					label="Institution"
					placeholder="e.g. University of Ghana"
					validation="length:0,100"
					validation-visibility="submit"
				/>

				<!-- Row 2: Course | Level -->
				<div class="sm:grid sm:grid-cols-2 sm:gap-x-4">
					<FormKit
						id="course"
						v-model="form.course"
						type="text"
						name="course"
						label="Course"
						placeholder="e.g. BSc Accounting"
						validation="required|length:2,100"
						validation-visibility="submit"
					/>
					<FormKit
						id="level"
						v-model="form.level"
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
				</div>

				<!-- Row 3: Qualification | Qualification Number -->
				<div class="sm:grid sm:grid-cols-2 sm:gap-x-4">
					<FormKit
						id="qualification"
						v-model="form.qualification"
						type="text"
						name="qualification"
						label="Qualification name"
						placeholder="e.g. Bachelor of Science"
						validation="length:0,100"
						validation-visibility="submit"
					/>
					<FormKit
						id="qualification_number"
						v-model="form.qualification_number"
						type="text"
						name="qualification_number"
						label="Qualification number"
						placeholder="Certificate ID"
						validation="length:0,10"
						validation-visibility="submit"
					/>
				</div>

				<!-- Row 4: Year (narrow) -->
				<div class="w-1/2 sm:w-1/3 xl:w-1/4">
					<FormKit
						id="year"
						v-model="form.year"
						type="text"
						name="year"
						label="Year of graduation"
						placeholder="e.g. 2015"
						validation="length:0,4|matches:/^[0-9]*$/"
						validation-visibility="submit"
					/>
				</div>

				<!-- Step 1 actions -->
				<div
					class="flex justify-between items-center mt-6 pt-4 border-t border-gray-100 dark:border-gray-700"
				>
					<button
						type="button"
						class="text-sm font-semibold text-gray-600 dark:text-gray-300 hover:underline px-3 py-2 transition-colors"
						@click="emit('close')"
					>
						Cancel
					</button>

					<div class="flex items-center gap-3">
						<button
							type="button"
							class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline px-2 py-2 transition-colors"
							:disabled="isSubmitting"
							@click="clickSaveWithoutDocuments"
						>
							Save without document
						</button>
						<button
							type="submit"
							:disabled="isSubmitting"
							class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
							@click="pendingAction = 'next'"
						>
							Next: Add documents
						</button>
					</div>
				</div>
			</FormKit>
		</div>

		<!-- ── STEP 2: Supporting documents ──────────────────────────────── -->
		<div v-show="currentStep === 2">
			<div class="flex items-baseline justify-between mb-3">
				<h3
					class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
				>
					Supporting documents
				</h3>
				<span class="text-[11px] italic text-gray-500 dark:text-gray-400"
					>Optional — you can add or change documents later.</span
				>
			</div>

			<!-- Server errors for step-2 fields -->
			<div
				v-if="Object.keys(step2Errors).length"
				class="mb-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-xs text-red-700 dark:text-red-400 space-y-1"
			>
				<p v-for="(msg, key) in step2Errors" :key="key">
					{{ msg }}
				</p>
			</div>

			<!-- File picker -->
			<div class="mt-1 mb-3">
				<label
					class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
					>Files</label
				>
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

			<!-- File list with per-file type + title -->
			<ul
				v-if="selectedFiles.length > 0"
				class="space-y-3 mb-4"
			>
				<li
					v-for="(entry, index) in selectedFiles"
					:key="index"
					class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3"
				>
					<!-- File info row -->
					<div class="flex items-center gap-3 mb-2">
						<div
							class="w-9 h-9 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center flex-shrink-0"
						>
							<DocumentIcon class="h-4 w-4 text-gray-400" />
						</div>
						<div class="flex-1 min-w-0 text-xs">
							<p
								class="font-semibold text-gray-900 dark:text-gray-100 truncate"
							>
								{{ entry.file.name }}
							</p>
							<p class="text-gray-500 dark:text-gray-400">
								{{ formatSize(entry.file.size) }} &middot;
								{{ entry.file.type || "unknown type" }}
							</p>
						</div>
						<button
							type="button"
							class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors flex-shrink-0"
							:aria-label="`Remove ${entry.file.name}`"
							@click="removeFile(index)"
						>
							<XCircleIcon class="h-5 w-5" />
						</button>
					</div>

					<!-- Per-file metadata -->
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
						<div>
							<label
								:for="`doc-type-${index}`"
								class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
							>
								Document type
								<span class="text-red-500">*</span>
							</label>
							<select
								:id="`doc-type-${index}`"
								v-model="entry.document_type"
								class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-xs px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
								:class="{
									'border-red-400 dark:border-red-500':
										!entry.document_type,
								}"
							>
								<option value="" disabled>Select type</option>
								<option
									v-for="opt in documentTypes"
									:key="opt.value"
									:value="opt.value"
								>
									{{ opt.label }}
								</option>
							</select>
							<p
								v-if="!entry.document_type"
								class="mt-1 text-[11px] text-red-500"
							>
								Document type is required
							</p>
						</div>
						<div>
							<label
								:for="`doc-title-${index}`"
								class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
							>
								Document title
								<span class="text-red-500">*</span>
							</label>
							<input
								:id="`doc-title-${index}`"
								v-model="entry.document_title"
								type="text"
								maxlength="100"
								placeholder="e.g. Bachelor of Science Degree"
								class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-xs px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
								:class="{
									'border-red-400 dark:border-red-500':
										!entry.document_title.trim(),
								}"
							/>
							<p
								v-if="!entry.document_title.trim()"
								class="mt-1 text-[11px] text-red-500"
							>
								Document title is required
							</p>
						</div>
					</div>
				</li>
			</ul>

			<!-- Hint when files have incomplete metadata -->
			<p
				v-if="selectedFiles.length > 0 && hasIncompleteFiles"
				class="text-xs text-amber-600 dark:text-amber-400 mb-3"
			>
				Please fill in the document type and title for every file before
				saving.
			</p>

			<!-- Step 2 actions -->
			<div
				class="flex justify-between items-center mt-6 pt-4 border-t border-gray-100 dark:border-gray-700"
			>
				<button
					type="button"
					class="text-sm font-semibold text-gray-600 dark:text-gray-300 hover:underline px-3 py-2 transition-colors"
					:disabled="isSubmitting"
					@click="goBackToStep1"
				>
					← Back
				</button>

				<button
					type="button"
					:disabled="isSubmitting || hasIncompleteFiles"
					class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
					@click="submit"
				>
					<ArrowPathIcon
						v-if="isSubmitting"
						class="w-4 h-4 animate-spin"
					/>
					{{ saveLabel }}
				</button>
			</div>
		</div>
	</main>
</template>
