<script setup>
import { Inertia } from "@inertiajs/inertia";
import Transfer from "./partials/Transfer.vue";
import Modal from "@/Components/NewModal.vue";
import NewModal from "@/Components/NewModal.vue";
import DeleteTransfer from "@/Pages/Transfer/Delete.vue";
import { ref, watch } from "vue";
import { useToggle } from "@vueuse/core";
import TransferList from "./TransferList.vue";
import EditTransfer from "./partials/EditTransfer.vue";

const emit = defineEmits(["closeForm"]);
const openEditTransferModal = ref(false);
const toggleEditTransferModal = useToggle(openEditTransferModal);

const editModel = ref(null);
const editTransfer = (model) => {
    editModel.value = model;
    toggleEditTransferModal();
};

const openDeleteTransferModal = ref(false);
const toggleDeleteTransferModal = useToggle(openDeleteTransferModal);
const deleteModel = ref(null);
const confirmDeleteTransfer = (model) => {
    deleteModel.value = model;
    toggleDeleteTransferModal();
};
const deleteTransfer = (staff_id, unit_id) => {
    Inertia.delete(
        route("staff.transfer.delete", {
            staff: staff_id,
            unit: unit_id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toggleDeleteTransferModal();
            },
        }
    );
};
let props = defineProps({
    transfers: Array,
    staffName: String,
    institution: Number,
    showTransferForm: {
        type: Boolean,
        default: false,
    },
});

let openTransferModal = ref(props.showTransferForm);
let toggleTransferModal = () => {
    openTransferModal.value = false;
    emit("closeForm");
};

watch(
    () => props.showTransferForm,
    (value) => {
        if (value) {
            openTransferModal.value = true;
        }
    }
);
</script>
<template>
    <!-- Transfer History -->
    <main>
        <h2 class="sr-only">Transfer History</h2>
        <div
            class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
        >
            <dl class="flex flex-wrap">
                <div class="flex-auto pl-6 pt-6">
                    <dt
                        class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-50"
                    >
                        Transfer History
                    </dt>
                </div>
                <div class="flex-none self-end px-6 pt-4">
                    <button
                        @click="toggleTransferModal()"
                        class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
                    >
                        {{
                            transfers.length > 0 ? "Transfer" : "First Posting"
                        }}
                    </button>
                </div>
                <TransferList
                    @deleteTransfer="(model) => confirmDeleteTransfer(model)"
                    @editTransfer="(model) => editTransfer(model)"
                    :transfers="transfers"
                    class="w-full"
                />
            </dl>
        </div>
        <Modal @close="toggleTransferModal()" :show="openTransferModal">
            <Transfer
                @formSubmitted="toggleTransferModal()"
                :staff="staff"
                :institution="institution"
                :transfers="transfers"
            />
        </Modal>

        <NewModal
            @close="toggleEditTransferModal()"
            :show="openEditTransferModal"
        >
            <EditTransfer
                @formSubmitted="toggleEditTransferModal()"
                :institution="institution"
                :transfer="editModel"
            />
        </NewModal>

        <NewModal
            @close="toggleDeleteTransferModal()"
            :show="openDeleteTransferModal"
        >
            <DeleteTransfer
                @deleteConfirmed="
                    deleteTransfer(deleteModel.staff_id, deleteModel.unit_id)
                "
                :model="deleteModel"
                :staff="staffName"
            />
        </NewModal>
    </main>
</template>
