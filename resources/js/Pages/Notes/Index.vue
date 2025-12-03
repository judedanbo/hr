<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import TableHeader from "@/Components/TableHeader.vue";
import NoteList from "./partials/NoteList.vue";
import EditNoteForm from "./partials/EditNoteForm.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { useToggle } from "@vueuse/core";
import Delete from "@/Components/Delete.vue";

const navigation = computed(() => useNavigation(props.notes));

const props = defineProps({
    notes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    noteTypes: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

// Filter state
const selectedNoteType = ref(props.filters.note_type || "");
const dateFrom = ref(props.filters.date_from || "");
const dateTo = ref(props.filters.date_to || "");

// Modal states
const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);

const openEditModal = ref(false);
const toggleEditModal = useToggle(openEditModal);

const selectedNote = ref(null);

const searchNotes = (value) => {
    useSearch(value, route("notes.index"));
};

const applyFilters = () => {
    router.get(
        route("notes.index"),
        {
            note_type: selectedNoteType.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
            search: props.filters.search,
        },
        { preserveState: true, replace: true }
    );
};

const clearFilters = () => {
    selectedNoteType.value = "";
    dateFrom.value = "";
    dateTo.value = "";
    router.get(route("notes.index"), {}, { preserveState: true, replace: true });
};

const viewNote = (note) => {
    router.visit(route("notes.show", { note: note.id }));
};

const editNote = async (note) => {
    try {
        const response = await fetch(route("notes.edit", { note: note.id }));
        const data = await response.json();
        selectedNote.value = data;
        toggleEditModal();
    } catch (error) {
        console.error("Error fetching note data:", error);
    }
};

const deleteNote = (note) => {
    selectedNote.value = note;
    toggleDeleteModal();
};

const deleteConfirmed = () => {
    router.delete(route("notes.delete", { note: selectedNote.value.id }), {
        onSuccess: () => {
            toggleDeleteModal();
        },
    });
};

const BreadCrumpLinks = [{ name: "Notes", url: "" }];
</script>

<template>
    <MainLayout>
        <Head title="Notes" />
        <main
            v-if="permissions?.includes('view staff notes')"
            class="max-w-7xl mx-auto sm:px-6 lg:px-8"
        >
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div
                class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200 dark:border-gray-700"
            >
                <TableHeader
                    title="Notes"
                    :total="notes.total"
                    :search="filters.search"
                    class="w-4/6"
                    :show-action="false"
                    @search-entered="(value) => searchNotes(value)"
                />

                <!-- Filters -->
                <div
                    class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            Note Type
                        </label>
                        <select
                            v-model="selectedNoteType"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @change="applyFilters"
                        >
                            <option value="">All Types</option>
                            <option
                                v-for="noteType in noteTypes"
                                :key="noteType.value"
                                :value="noteType.value"
                            >
                                {{ noteType.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            Date From
                        </label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @change="applyFilters"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            Date To
                        </label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <div v-if="selectedNoteType || dateFrom || dateTo" class="mb-4">
                    <button
                        class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                        @click="clearFilters"
                    >
                        Clear all filters
                    </button>
                </div>

                <NoteList
                    :notes="notes.data"
                    @view-note="viewNote"
                    @edit-note="editNote"
                    @delete-note="deleteNote"
                >
                    <template #pagination>
                        <Pagination :navigation="navigation" />
                    </template>
                </NoteList>
            </div>
        </main>
        <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600 dark:text-gray-400">
                    You do not have permission to view notes.
                </p>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :show="openEditModal" @close="toggleEditModal()">
            <EditNoteForm
                v-if="selectedNote"
                :note="selectedNote.note"
                :note-types="selectedNote.noteTypes"
                @form-submitted="toggleEditModal()"
            />
        </Modal>

        <!-- Delete Confirmation -->
        <Delete
            :show="openDeleteModal"
            :model="selectedNote"
            model-name="note"
            @close="toggleDeleteModal"
            @delete-confirmed="deleteConfirmed"
        />
    </MainLayout>
</template>
