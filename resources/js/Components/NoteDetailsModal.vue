<script setup>
import { ref, computed, watch } from "vue";
import {
	Dialog,
	DialogPanel,
	DialogTitle,
	TransitionChild,
	TransitionRoot,
} from "@headlessui/vue";
import {
	XMarkIcon,
	ChevronLeftIcon,
	ChevronRightIcon,
	ArrowDownTrayIcon,
	DocumentIcon,
} from "@heroicons/vue/24/outline";
import Avatar from "@/Components/Avatar.vue";

const props = defineProps({
	show: Boolean,
	note: Object,
});

const emit = defineEmits(["close"]);

const currentDocumentIndex = ref(0);

const currentDocument = computed(() => {
	if (!props.note?.url || props.note.url.length === 0) {
		return null;
	}
	return props.note.url[currentDocumentIndex.value];
});

const hasDocuments = computed(() => props.note?.url?.length > 0);
const hasMultipleDocuments = computed(() => props.note?.url?.length > 1);

const canViewInline = computed(() => {
	if (!currentDocument.value) return false;
	const type = currentDocument.value.file_type.toLowerCase();
	return type.includes("pdf") || type.includes("image");
});

const documentUrl = computed(() => {
	if (!currentDocument.value) return "";
	return `/documents/${currentDocument.value.file_name}`;
});

function nextDocument() {
	if (currentDocumentIndex.value < props.note.url.length - 1) {
		currentDocumentIndex.value++;
	}
}

function previousDocument() {
	if (currentDocumentIndex.value > 0) {
		currentDocumentIndex.value--;
	}
}

function downloadDocument() {
	if (!currentDocument.value) return;
	const link = document.createElement("a");
	link.href = documentUrl.value;
	link.download = currentDocument.value.document_title;
	link.click();
}

// Reset index when modal opens
watch(
	() => props.show,
	(newValue) => {
		if (newValue) {
			currentDocumentIndex.value = 0;
		}
	},
);
</script>

<template>
	<TransitionRoot as="template" :show="show">
		<Dialog as="div" class="relative z-50" @close="emit('close')">
			<!-- Backdrop -->
			<TransitionChild
				enter="ease-out duration-300"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="ease-in duration-200"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75" />
			</TransitionChild>

			<div class="fixed inset-0 z-50 w-screen overflow-y-auto">
				<div class="flex min-h-full items-center justify-center p-4">
					<TransitionChild
						enter="ease-out duration-300"
						enter-from="opacity-0 translate-y-4 sm:scale-95"
						enter-to="opacity-100 translate-y-0 sm:scale-100"
						leave="ease-in duration-200"
						leave-from="opacity-100 translate-y-0 sm:scale-100"
						leave-to="opacity-0 translate-y-4 sm:scale-95"
					>
						<DialogPanel
							class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl w-full max-w-4xl"
						>
							<!-- Header -->
							<div
								class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600"
							>
								<div class="flex items-start justify-between">
									<div v-if="note" class="flex items-start space-x-3">
										<Avatar :person_id="note.created_by" class="h-10 w-10" />
										<div>
											<DialogTitle
												class="text-lg font-semibold text-gray-900 dark:text-gray-50"
											>
												Note Details
											</DialogTitle>
											<div class="mt-1 flex items-center gap-2">
												<span
													v-if="note.note_type"
													class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/20 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-400"
												>
													{{ note.note_type }}
												</span>
												<time class="text-sm text-gray-500 dark:text-gray-400">
													{{ note.note_date }}
												</time>
											</div>
										</div>
									</div>
									<button
										@click="emit('close')"
										class="rounded-md text-gray-400 hover:text-gray-500"
									>
										<XMarkIcon class="h-6 w-6" />
									</button>
								</div>
							</div>

							<!-- Note Content -->
							<div
								v-if="note"
								class="px-6 py-4 border-b border-gray-200 dark:border-gray-600"
							>
								<p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
									{{ note.note }}
								</p>
							</div>

							<!-- Attachments Section -->
							<div v-if="hasDocuments" class="px-6 py-4">
								<!-- Document Navigation Header -->
								<div class="flex items-center justify-between mb-4">
									<h3
										class="text-sm font-medium text-gray-900 dark:text-gray-50"
									>
										Attachments
										<span class="text-gray-500"
											>({{ currentDocumentIndex + 1 }}
											of
											{{ note.url.length }})</span
										>
									</h3>
									<div class="flex items-center gap-2">
										<button
											v-if="hasMultipleDocuments"
											@click="previousDocument"
											:disabled="currentDocumentIndex === 0"
											class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
										>
											<ChevronLeftIcon class="h-5 w-5" />
										</button>
										<button
											@click="downloadDocument"
											class="inline-flex items-center gap-1 px-3 py-2 text-sm rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
										>
											<ArrowDownTrayIcon class="h-4 w-4" />
											Download
										</button>
										<button
											v-if="hasMultipleDocuments"
											@click="nextDocument"
											:disabled="currentDocumentIndex === note.url.length - 1"
											class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
										>
											<ChevronRightIcon class="h-5 w-5" />
										</button>
									</div>
								</div>

								<!-- Document File Name -->
								<p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
									{{ currentDocument.document_title }}
								</p>

								<!-- Document Preview Area -->
								<div
									class="bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden"
								>
									<!-- PDF Preview -->
									<template
										v-if="
											canViewInline && currentDocument.file_type.includes('pdf')
										"
									>
										<embed
											:src="documentUrl"
											type="application/pdf"
											class="w-full h-[600px]"
										/>
									</template>

									<!-- Image Preview -->
									<template
										v-else-if="
											canViewInline &&
											currentDocument.file_type.includes('image')
										"
									>
										<img
											:src="documentUrl"
											:alt="currentDocument.document_title"
											class="w-full h-auto max-h-[600px] object-contain"
										/>
									</template>

									<!-- DOC/DOCX Cannot Preview -->
									<template v-else>
										<div
											class="flex flex-col items-center justify-center py-16 px-4"
										>
											<DocumentIcon
												class="h-16 w-16 text-gray-400 dark:text-gray-500 mb-4"
											/>
											<p
												class="text-gray-700 dark:text-gray-300 text-center mb-2"
											>
												Cannot preview this file type
											</p>
											<p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
												{{ currentDocument.document_title }}
											</p>
											<button
												@click="downloadDocument"
												class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
											>
												<ArrowDownTrayIcon class="h-5 w-5" />
												Download to view
											</button>
										</div>
									</template>
								</div>
							</div>

							<!-- No Attachments Message -->
							<div v-else class="px-6 py-8 text-center">
								<p class="text-gray-500 dark:text-gray-400">No attachments</p>
							</div>
						</DialogPanel>
					</TransitionChild>
				</div>
			</div>
		</Dialog>
	</TransitionRoot>
</template>
