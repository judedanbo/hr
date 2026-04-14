<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import TableHeader from "@/Components/TableHeader.vue";
import DocumentList from "./partials/DocumentList.vue";
import EditDocumentForm from "./partials/EditDocumentForm.vue";
import UploadForm from "./partials/UploadForm.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { useToggle } from "@vueuse/core";
import Delete from "@/Components/Delete.vue";

const navigation = computed(() => useNavigation(props.documents));

const props = defineProps({
    documents: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    documentTypes: { type: Array, default: () => [] },
    documentStatuses: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const selectedDocumentType = ref(props.filters.document_type || "");
const selectedDocumentStatus = ref(props.filters.document_status || "");

const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);
const openEditModal = ref(false);
const toggleEditModal = useToggle(openEditModal);
const openUploadModal = ref(false);
const toggleUploadModal = useToggle(openUploadModal);
const selectedDocument = ref(null);

const searchDocuments = (value) => useSearch(value, route("document.index"));

const applyFilters = () => {
    router.get(route("document.index"), {
        document_type: selectedDocumentType.value,
        document_status: selectedDocumentStatus.value,
        search: props.filters.search,
    }, { preserveState: true, replace: true });
};

const editDocument = async (document) => {
    const response = await fetch(route("document.edit", { document: document.id }));
    selectedDocument.value = await response.json();
    toggleEditModal();
};

const deleteDocument = (document) => {
    selectedDocument.value = document;
    toggleDeleteModal();
};

const deleteConfirmed = () => {
    router.delete(route("document.destroy", { document: selectedDocument.value.id }), {
        onSuccess: () => toggleDeleteModal(),
    });
};

const BreadCrumpLinks = [{ name: "Documents", url: "" }];
</script>

<template>
    <MainLayout>
        <Head title="Documents" />
        <main v-if="permissions?.includes('view documents')" class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200 dark:border-gray-700">
                <TableHeader
                    title="Documents"
                    :total="documents.total"
                    :search="filters.search"
                    :show-action="permissions?.includes('create documents')"
                    action-text="Upload Document"
                    @search-entered="searchDocuments"
                    @action-clicked="toggleUploadModal()"
                />

                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Type</label>
                            <select v-model="selectedDocumentType" @change="applyFilters" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                                <option value="">All Types</option>
                                <option v-for="dt in documentTypes" :key="dt.value" :value="dt.value">{{ dt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select v-model="selectedDocumentStatus" @change="applyFilters" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                                <option value="">All Statuses</option>
                                <option v-for="ds in documentStatuses" :key="ds.value" :value="ds.value">{{ ds.label }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <DocumentList :documents="documents.data" @edit-document="editDocument" @delete-document="deleteDocument">
                    <template #pagination><Pagination :navigation="navigation" /></template>
                </DocumentList>
            </div>
        </main>
        <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg"><p class="text-gray-600 dark:text-gray-400">You do not have permission to view documents.</p></div>
        </div>

        <Modal :show="openUploadModal" @close="toggleUploadModal()">
            <UploadForm :document-types="documentTypes" :document-statuses="documentStatuses" @form-submitted="toggleUploadModal()" />
        </Modal>

        <Modal :show="openEditModal" @close="toggleEditModal()">
            <EditDocumentForm v-if="selectedDocument" :document="selectedDocument.document" :document-types="selectedDocument.documentTypes" :document-statuses="selectedDocument.documentStatuses" @form-submitted="toggleEditModal()" />
        </Modal>
        <Delete :show="openDeleteModal" :model="selectedDocument" model-name="document" @close="toggleDeleteModal" @delete-confirmed="deleteConfirmed" />
    </MainLayout>
</template>
