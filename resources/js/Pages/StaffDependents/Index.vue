<script setup>
import Modal from "@/Components/NewModal.vue";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

import { useToggle } from "@vueuse/core";
import AddDependant from "./Add.vue";
import EditDependant from "./Edit.vue";
import DeleteDependant from "./Delete.vue";
import List from "./partials/List.vue";

defineProps({
    dependents: Array,
    staff_id: Number,
});
const selectedDependent = ref(null);

// edit dependent
const showEditModel = ref(false);
const toggleEditDependent = useToggle(showEditModel);

const editDependent = (model) => {
    selectedDependent.value = model;
    toggleEditDependent();
};

// delete dependent
const showDeleteModel = ref(false);
const toggleDeleteDependent = useToggle(showDeleteModel);

let showAddDependantForm = ref(false);
let toggleAddDependantFrom = useToggle(showAddDependantForm);

const confirmDelete = (model) => {
    selectedDependent.value = model;
    toggleDeleteDependent();
};

const deleteDependent = () => {
    Inertia.delete(
        route("dependent.delete", {
            dependent: selectedDependent.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toggleDeleteDependent();
            },
        }
    );
};
</script>
<template>
    <!-- dependent History -->
    <main class="w-full">
        <h2 class="sr-only">Staff Dependents</h2>
        <div
            class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-500/80"
        >
            <dl class="flex flex-wrap">
                <div class="flex-auto pl-6 pt-6">
                    <dt
                        class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-100"
                    >
                        Staff Dependents
                    </dt>
                </div>
                <div class="flex-none self-end px-6 pt-4">
                    <button
                        v-if="staff_id"
                        @click.prevent="toggleAddDependantFrom()"
                        class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
                    >
                        Add dependent
                    </button>
                </div>
                <List
                    @editDependent="(model) => editDependent(model)"
                    @deleteDependent="(model) => confirmDelete(model)"
                    :dependents="dependents"
                />
            </dl>
        </div>
        <Modal @close="toggleAddDependantFrom()" :show="showAddDependantForm">
            <AddDependant
                @formSubmitted="toggleAddDependantFrom()"
                :staff_id="staff_id"
            />
        </Modal>
        <Modal @close="toggleEditDependent()" :show="showEditModel">
            <EditDependant
                @formSubmitted="toggleEditDependent()"
                :staff_id="staff_id"
                :dependent="selectedDependent"
            />
        </Modal>
        <Modal @close="toggleDeleteDependent()" :show="showDeleteModel">
            <DeleteDependant
                @close="toggleDeleteDependent()"
                @deleteConfirmed="deleteDependent()"
                :person="selectedDependent.name"
            />
        </Modal>
    </main>
</template>
