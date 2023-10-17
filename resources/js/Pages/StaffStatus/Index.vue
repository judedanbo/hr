<script setup>
import Create from "./Create.vue";
import EditStaffStatus from "./Edit.vue";
import DeleteStaffStatus from "./Delete.vue";
import Modal from "@/Components/NewModal.vue";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useToggle } from "@vueuse/core";
import StaffStatusHistory from "./partials/StaffStatusHistory.vue";

const emit = defineEmits(["closeForm", "editHistory", "deleteHistory"]);

let props = defineProps({
    statuses: Array,
    staff: Object,
    institution: Number,
});

let openStatusModal = ref(false);
const toggleStatusModal = useToggle(openStatusModal);

let openEditStaffModal = ref(false);
const toggleEditStaffStatusModal = useToggle(openEditStaffModal);

const staffStatus = ref(null);
const editStaffStatus = (modal) => {
    staffStatus.value = modal;
    toggleEditStaffStatusModal();
};

let openDeleteStaffStatusModal = ref(false);
const toggleDeleteStaffStatusModal = useToggle(openDeleteStaffStatusModal);

const confirmDelete = (model) => {
    staffStatus.value = model;
    toggleDeleteStaffStatusModal();
};
const deleteStaffStatus = () => {
    Inertia.delete(
        route("staff-status.delete", {
            staff: props.staff.id,
            staffStatus: staffStatus.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                staffStatus.value = null;
                toggleDeleteStaffStatusModal();
            },
        }
    );
};
</script>
<template>
    <!-- Transfer History -->
    <main>
        <h2 class="sr-only">Status History</h2>
        <div
            class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
        >
            <dl class="flex flex-wrap">
                <div class="flex-auto pl-6 pt-6">
                    <dt
                        class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-50"
                    >
                        Status History
                    </dt>
                </div>
                <div class="flex-none self-end px-6 pt-4">
                    <button
                        @click="toggleStatusModal()"
                        class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
                    >
                        {{ "Change" }}
                    </button>
                </div>
                <StaffStatusHistory
                    @editStaffStatus="(model) => editStaffStatus(model)"
                    @deleteStaffStatus="(model) => confirmDelete(model)"
                    :statuses="statuses"
                />
            </dl>
        </div>
        <Modal @close="toggleStatusModal()" :show="openStatusModal">
            <Create
                @formSubmitted="toggleStatusModal()"
                :staff="staff"
                :institution="institution"
                :statuses="statuses"
            />
        </Modal>
        <!-- Edit staff History Modal -->
        <Modal @close="toggleEditStaffStatusModal()" :show="openEditStaffModal">
            <EditStaffStatus
                @formSubmitted="toggleEditStaffStatusModal()"
                :institution="institution"
                :staffStatus="staffStatus"
                :staff="staff"
            />
        </Modal>

        <!-- Delete staff History Modal -->
        <Modal
            @close="toggleDeleteStaffStatusModal()"
            :show="openDeleteStaffStatusModal"
        >
            <DeleteStaffStatus
                @close="toggleDeleteStaffStatusModal()"
                @deleteConfirmed="deleteStaffStatus()"
            />
        </Modal>
    </main>
</template>
