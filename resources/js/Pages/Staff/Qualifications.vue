<script setup>
import SubMenu from "@/Components/SubMenu.vue";
import { Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import AddQualification from "@/Pages/Qualification/Add.vue";
import EditQualification from "@/Pages/Qualification/Edit.vue";
import DeleteQualification from "@/Pages/Qualification/Delete.vue";
import Modal from "@/Components/NewModal.vue";
import { ref } from "vue";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";
import QualificationList from "../Qualification/Staff.vue";

// Edit Qualification
const openEditModal = ref(false);
const toggleEditModal = useToggle(openEditModal);

// Delete Qualification
const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);

defineProps({
    qualifications: Array,
    person: Object,
});
let openQualificationModal = ref(false);
let toggleQualificationModal = useToggle(openQualificationModal);

const qualificationModel = ref(null);

const editQualification = (model) => {
    qualificationModel.value = model;
    toggleEditModal();
};
const confirmDelete = (model) => {
    qualificationModel.value = model;
    toggleDeleteModal();
};
const deleteQualification = () => {
    Inertia.delete(
        route("qualification.delete", {
            qualification: qualificationModel.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                qualificationModel.value = null;
                toggleDeleteModal();
            },
        }
    );
};
</script>
<template>
    <!-- Transfer History -->
    <main>
        <h2 class="sr-only">staff's Qualifications</h2>
        <div
            class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
        >
            <dl class="flex flex-wrap">
                <div class="flex-auto pl-6 pt-6">
                    <dt
                        class="text-xl font-semibold leading-6 text-gray-900 dark:text-gray-50"
                    >
                        Qualifications
                    </dt>
                </div>
                <div class="flex-none self-end px-6 pt-4">
                    <button
                        @click="toggleQualificationModal()"
                        class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
                    >
                        {{ "Add Qualification" }}
                    </button>
                </div>
                <QualificationList
                    @editQualification="(model) => editQualification(model)"
                    @deleteQualification="(model) => confirmDelete(model)"
                    :qualifications="qualifications"
                />
            </dl>
        </div>
        <NewModal
            @close="toggleQualificationModal()"
            :show="openQualificationModal"
        >
            <AddQualification
                @formSubmitted="toggleQualificationModal()"
                :person="person.id"
            />
        </NewModal>
        <NewModal @close="toggleEditModal()" :show="openEditModal">
            <EditQualification
                @formSubmitted="toggleEditModal()"
                :person="person.id"
                :qualification="qualificationModel"
            />
        </NewModal>

        <!-- Delete Modal -->
        <NewModal @close="toggleDeleteModal()" :show="openDeleteModal">
            <DeleteQualification
                @close="toggleDeleteModal()"
                @deleteConfirmed="deleteQualification()"
                :person="person.name"
            />
        </NewModal>
    </main>
</template>
