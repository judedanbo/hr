<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import DocumentPreview from "@/Pages/Qualification/partials/DocumentPreview.vue";
import {
	CheckCircleIcon,
	AcademicCapIcon,
	CheckIcon,
	XMarkIcon,
} from "@heroicons/vue/24/outline";
import { PaperClipIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
	qualifications: {
		type: Array,
		required: true,
	},
	can: {
		type: Object,
		default: () => ({}),
	},
});

const breadcrumbLinks = [
	{
		name: "Data Integrity",
		href: route("data-integrity.index"),
	},
	{
		name: "Pending Qualifications",
	},
];

// Document preview state
const showPreviewDocumentModal = ref(false);
const togglePreviewDocumentModal = useToggle(showPreviewDocumentModal);
const selectedDocuments = ref([]);
const currentDocumentIndex = ref(0);

const openDocumentPreview = (qualification) => {
	selectedDocuments.value = qualification.documents || [];
	currentDocumentIndex.value = 0;
	togglePreviewDocumentModal();
};

const currentDocument = computed(() =>
	selectedDocuments.value[currentDocumentIndex.value] || null,
);

const nextDocument = () => {
	if (currentDocumentIndex.value < selectedDocuments.value.length - 1) {
		currentDocumentIndex.value++;
	}
};

const prevDocument = () => {
	if (currentDocumentIndex.value > 0) {
		currentDocumentIndex.value--;
	}
};

const approveQualification = (qualification) => {
	router.patch(
		route("qualification.approve", { qualification: qualification.id }),
		{},
		{
			preserveScroll: true,
		},
	);
};

const rejectQualification = (qualification) => {
	router.patch(
		route("qualification.reject", { qualification: qualification.id }),
		{},
		{
			preserveScroll: true,
		},
	);
};
</script>

<template>
	<MainLayout>
		<Head title="Pending Qualifications" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6">
						<h1
							class="text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							Qualifications Pending Approval
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							{{ qualifications.length }}
							{{
								qualifications.length === 1
									? "qualification requires"
									: "qualifications require"
							}}
							review and approval
						</p>
					</div>

					<!-- No Issues State -->
					<div
						v-if="qualifications.length === 0"
						class="rounded-lg bg-green-50 dark:bg-green-900/20 p-8 text-center"
					>
						<CheckCircleIcon
							class="mx-auto h-12 w-12 text-green-600 dark:text-green-400"
						/>
						<h3
							class="mt-4 text-lg font-semibold text-green-900 dark:text-green-100"
						>
							No Pending Qualifications
						</h3>
						<p class="mt-2 text-sm text-green-700 dark:text-green-300">
							All submitted qualifications have been reviewed.
						</p>
					</div>

					<!-- Qualifications List -->
					<div v-else class="space-y-3">
						<div
							v-for="qualification in qualifications"
							:key="qualification.id"
							class="rounded-lg border border-amber-200 dark:border-amber-800 bg-white dark:bg-gray-800 p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
						>
							<div class="flex items-start gap-4">
								<div
									class="flex-shrink-0 h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center"
								>
									<AcademicCapIcon
										class="h-6 w-6 text-amber-600 dark:text-amber-400"
									/>
								</div>
								<div class="flex-1 min-w-0">
									<div class="flex items-center gap-2">
										<h3
											class="text-lg font-semibold text-gray-900 dark:text-gray-100"
										>
											{{ qualification.name }}
										</h3>
										<span
											v-if="qualification.staff_number"
											class="text-sm text-gray-500 dark:text-gray-400"
										>
											({{ qualification.staff_number }})
										</span>
										<PaperClipIcon
											v-if="qualification.documents?.length > 0"
											class="w-5 h-5 text-gray-400 dark:text-gray-300 cursor-pointer hover:text-green-600 dark:hover:text-green-400"
											title="Click to preview documents"
											@click="openDocumentPreview(qualification)"
										/>
									</div>
									<div class="mt-2 space-y-1">
										<p
											class="text-sm text-gray-700 dark:text-gray-300"
										>
											<span class="font-medium"
												>Qualification:</span
											>
											{{
												qualification.qualification ||
												qualification.course
											}}
										</p>
										<p
											class="text-sm text-gray-500 dark:text-gray-400"
										>
											{{ qualification.institution }}
											<span v-if="qualification.level">
												- {{ qualification.level }}
											</span>
											<span v-if="qualification.year">
												({{ qualification.year }})
											</span>
										</p>
										<p
											class="text-xs text-gray-400 dark:text-gray-500"
										>
											Submitted:
											{{ qualification.created_at }}
										</p>
									</div>
								</div>
								<div
									v-if="can.approve"
									class="flex-shrink-0 flex gap-2"
								>
									<button
										type="button"
										class="inline-flex items-center gap-1 rounded-md bg-green-50 dark:bg-green-900/30 px-3 py-2 text-sm font-semibold text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors"
										@click="
											approveQualification(qualification)
										"
									>
										<CheckIcon class="h-4 w-4" />
										Approve
									</button>
									<button
										type="button"
										class="inline-flex items-center gap-1 rounded-md bg-red-50 dark:bg-red-900/30 px-3 py-2 text-sm font-semibold text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors"
										@click="
											rejectQualification(qualification)
										"
									>
										<XMarkIcon class="h-4 w-4" />
										Reject
									</button>
								</div>
							</div>
						</div>
					</div>

					<div
						v-if="qualifications.length > 0 && !can.approve"
						class="mt-6 rounded-md bg-blue-50 dark:bg-blue-900/20 p-4"
					>
						<p class="text-sm text-blue-700 dark:text-blue-300">
							<strong>Note:</strong> You do not have permission to
							approve qualifications. Please contact an
							administrator if you need to review these.
						</p>
					</div>
				</div>
			</div>
		</main>

		<!-- Document Preview Modal -->
		<Modal :show="showPreviewDocumentModal" @close="togglePreviewDocumentModal">
			<DocumentPreview
				v-if="currentDocument"
				:url="'/storage/qualifications/' + currentDocument.file_name"
				:type="currentDocument.file_type"
				:title="currentDocument.document_title"
				:current-index="currentDocumentIndex"
				:total-count="selectedDocuments.length"
				@prev="prevDocument"
				@next="nextDocument"
			/>
		</Modal>
	</MainLayout>
</template>
