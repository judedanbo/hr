<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import TableHeader from "@/Components/TableHeader.vue";
import ContactList from "./partials/ContactList.vue";
import EditContactForm from "./partials/EditContactForm.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { useToggle } from "@vueuse/core";
import Delete from "@/Components/Delete.vue";

const navigation = computed(() => useNavigation(props.contacts));

const props = defineProps({
    contacts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    contactTypes: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const selectedContactType = ref(props.filters.contact_type || "");

const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);
const openEditModal = ref(false);
const toggleEditModal = useToggle(openEditModal);
const selectedContact = ref(null);

const searchContacts = (value) => useSearch(value, route("contact.index"));

const applyFilters = () => {
    router.get(route("contact.index"), {
        contact_type: selectedContactType.value,
        search: props.filters.search,
    }, { preserveState: true, replace: true });
};

const editContact = async (contact) => {
    const response = await fetch(route("contact.edit", { contact: contact.id }));
    selectedContact.value = await response.json();
    toggleEditModal();
};

const deleteContact = (contact) => {
    selectedContact.value = contact;
    toggleDeleteModal();
};

const deleteConfirmed = () => {
    router.delete(route("contact.destroy", { contact: selectedContact.value.id }), {
        onSuccess: () => toggleDeleteModal(),
    });
};

const BreadCrumpLinks = [{ name: "Contacts", url: "" }];
</script>

<template>
    <MainLayout>
        <Head title="Contacts" />
        <main v-if="permissions?.includes('view contacts')" class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200 dark:border-gray-700">
                <TableHeader title="Contacts" :total="contacts.total" :search="filters.search" :show-action="false" @search-entered="searchContacts" />

                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Type</label>
                    <select v-model="selectedContactType" @change="applyFilters" class="w-full md:w-1/4 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                        <option value="">All Types</option>
                        <option v-for="ct in contactTypes" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
                    </select>
                </div>

                <ContactList :contacts="contacts.data" @edit-contact="editContact" @delete-contact="deleteContact">
                    <template #pagination><Pagination :navigation="navigation" /></template>
                </ContactList>
            </div>
        </main>
        <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg"><p class="text-gray-600 dark:text-gray-400">You do not have permission to view contacts.</p></div>
        </div>

        <Modal :show="openEditModal" @close="toggleEditModal()">
            <EditContactForm v-if="selectedContact" :contact="selectedContact.contact" :contact-types="selectedContact.contactTypes" @form-submitted="toggleEditModal()" />
        </Modal>
        <Delete :show="openDeleteModal" :model="selectedContact" model-name="contact" @close="toggleDeleteModal" @delete-confirmed="deleteConfirmed" />
    </MainLayout>
</template>
