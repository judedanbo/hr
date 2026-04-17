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
	document_type: "",
});

// ── Step-1 validation errors ──────────────────────────────────────────────────
const step1Errors = ref({});

// ── Step-2: selected files ────────────────────────────────────────────────────
const selectedFiles = ref([]);

function onFileInput(event) {
	const files = Array.from(event.target.files || []);
	selectedFiles.value = [...selectedFiles.value, ...files];
	// Reset the input so the same file can be added again if needed
	event.target.value = "";
}

function removeFile(index) {
	selectedFiles.value = selectedFiles.value.filter((_, i) => i !== index);
}

function formatSize(bytes) {
	return (bytes / 1024).toFixed(0) + " KB";
}

// ── Step 1 → 2 ───────────────────────────────────────────────────────────────
const step1FormRef = ref(null);

async function goToStep2() {
	step1Errors.value = {};
	// Trigger FormKit validation
	const valid = await step1FormRef.value.node.submit();
	if (valid === false) {
		return;
	}
	currentStep.value = 2;
}

function goBackToStep1() {
	currentStep.value = 1;
}

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
		fd.append("document_type", form.value.document_type ?? "");
		selectedFiles.value.forEach((file) => {
			fd.append("file_name[]", file);
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
				document_type: "",
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

// "Skip — save without documents" from step 1 submits directly
async function saveWithoutDocuments() {
	step1Errors.value = {};
	const valid = await step1FormRef.value.node.submit();
	if (valid === false) {
		return;
	}
	selectedFiles.value = [];
	submit();
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
				ref="step1FormRef"
				type="form"
				:disabled="isSubmitting"
				:actions="false"
				@submit="goToStep2"
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
				<div
					class="w-1/2 sm:w-1/3 xl:w-1/4"
				>
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
							@click="saveWithoutDocuments"
						>
							Save without document
						</button>
						<button
							type="submit"
							:disabled="isSubmitting"
							class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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

			<!-- Document type -->
			<FormKit
				v-model="form.document_type"
				type="select"
				name="document_type"
				label="Document type"
				placeholder="Select type"
				:options="documentTypes"
				:validation="
					selectedFiles.length > 0 ? 'required' : ''
				"
				validation-visibility="submit"
			/>

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

			<!-- File list -->
			<ul
				v-if="selectedFiles.length > 0"
				class="space-y-2 mb-4"
			>
				<li
					v-for="(file, index) in selectedFiles"
					:key="index"
					class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-2.5"
				>
					<div
						class="w-9 h-9 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center flex-shrink-0"
					>
						<DocumentIcon class="h-4 w-4 text-gray-400" />
					</div>
					<div class="flex-1 min-w-0 text-xs">
						<p
							class="font-semibold text-gray-900 dark:text-gray-100 truncate"
						>
							{{ file.name }}
						</p>
						<p class="text-gray-500 dark:text-gray-400">
							{{ formatSize(file.size) }} &middot;
							{{ file.type || "unknown type" }}
						</p>
					</div>
					<button
						type="button"
						class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors flex-shrink-0"
						:aria-label="`Remove ${file.name}`"
						@click="removeFile(index)"
					>
						<XCircleIcon class="h-5 w-5" />
					</button>
				</li>
			</ul>

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
					:disabled="isSubmitting"
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
