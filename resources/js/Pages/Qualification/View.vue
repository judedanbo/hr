<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import DocumentPreview from "./partials/DocumentPreview.vue";

const emit = defineEmits(["close"]);

const props = defineProps({
	qualification: { type: Object, required: true },
});

const documents = computed(() => props.qualification.documents ?? []);
const currentDoc = ref(0);
const confirming = ref(false);

// Reset confirm state whenever the current document changes.
watch(currentDoc, () => {
	confirming.value = false;
});

const detailRows = computed(() =>
	[
		{ key: "Course", value: props.qualification.course },
		{ key: "Qualification name", value: props.qualification.qualification },
		{
			key: "Qualification number",
			value: props.qualification.qualification_number,
		},
		{ key: "Institution", value: props.qualification.institution },
		{ key: "Level", value: props.qualification.level },
		{ key: "Year", value: props.qualification.year },
	].filter((r) => r.value),
);

function statusClass(status) {
	const tone = (status ?? "").toLowerCase();
	if (tone.includes("approved")) {
		return "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200";
	}
	if (tone.includes("pending")) {
		return "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200";
	}
	if (tone.includes("rejected")) {
		return "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200";
	}
	return "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200";
}

function docUrl(doc) {
	return doc?.file_name ? `/storage/qualifications/${doc.file_name}` : null;
}

function confirmDeleteDocument() {
	const doc = documents.value[currentDoc.value];
	if (!doc) {
		return;
	}

	router.delete(
		route("qualification-document.destroy", {
			qualification: props.qualification.id,
			document: doc.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				if (
					currentDoc.value >= documents.value.length - 1 &&
					currentDoc.value > 0
				) {
					currentDoc.value -= 1;
				}
				router.reload({ only: ["qualifications"] });
				confirming.value = false;
			},
		},
	);
}
</script>

<template>
	<main
		class="bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 shadow-sm max-w-3xl"
	>
		<header class="flex justify-between items-start mb-5">
			<div>
				<h2 class="text-lg font-bold text-gray-900 dark:text-gray-50">
					Qualification details
				</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
					Read-only preview of your qualification and attached documents.
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

		<!-- Details -->
		<section>
			<div class="flex items-start justify-between mb-3">
				<h3
					class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
				>
					Details
				</h3>
				<span
					v-if="qualification.status"
					:class="[
						'text-[11px] font-semibold px-2.5 py-1 rounded-full',
						statusClass(qualification.status),
					]"
				>
					{{ qualification.status }}
				</span>
			</div>
			<dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
				<div
					v-for="row in detailRows"
					:key="row.key"
					class="flex justify-between gap-2 py-1.5"
				>
					<dt class="text-gray-500 dark:text-gray-400 shrink-0">
						{{ row.key }}
					</dt>
					<dd
						class="min-w-0 text-right font-medium text-gray-900 dark:text-gray-100 truncate"
					>
						{{ row.value }}
					</dd>
				</div>
			</dl>
		</section>

		<!-- Documents -->
		<section class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
			<div class="flex items-center justify-between mb-3">
				<h3
					class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
				>
					Documents ({{ documents.length }})
				</h3>

				<!-- Delete control — only for editable qualifications with documents -->
				<template v-if="qualification.can_edit && documents.length > 0">
					<!-- Normal state: show Delete button -->
					<button
						v-if="!confirming"
						type="button"
						class="text-red-600 dark:text-red-400 hover:underline text-xs font-semibold"
						@click="confirming = true"
					>
						Delete this document
					</button>

					<!-- Confirming state: inline prompt -->
					<div v-else class="flex items-center gap-2">
						<span class="text-xs text-red-600 dark:text-red-400 font-medium">
							Delete this document?
						</span>
						<button
							type="button"
							class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
							@click="confirming = false"
						>
							Cancel
						</button>
						<button
							type="button"
							class="inline-flex items-center rounded bg-red-600 hover:bg-red-700 px-2 py-0.5 text-xs font-semibold text-white transition-colors"
							@click="confirmDeleteDocument"
						>
							Confirm delete
						</button>
					</div>
				</template>
			</div>

			<div
				v-if="documents.length === 0"
				class="text-sm text-gray-500 dark:text-gray-400 py-2"
			>
				No documents attached.
			</div>
			<DocumentPreview
				v-else
				:url="docUrl(documents[currentDoc])"
				:type="documents[currentDoc]?.file_type"
				:title="
					documents[currentDoc]?.document_title ||
					documents[currentDoc]?.document_type
				"
				:current-index="currentDoc"
				:total-count="documents.length"
				@prev="currentDoc = Math.max(0, currentDoc - 1)"
				@next="currentDoc = Math.min(documents.length - 1, currentDoc + 1)"
			/>
		</section>

		<!-- Footer -->
		<div
			class="flex justify-end mt-6 pt-4 border-t border-gray-100 dark:border-gray-700"
		>
			<button
				type="button"
				class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-bold text-white shadow-sm transition-colors"
				@click="emit('close')"
			>
				Close
			</button>
		</div>
	</main>
</template>
