<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import DeleteContactsModal from "./DeleteContactsModal.vue";
import AddContactsModal from "./AddContactsModal.vue";
import { format, differenceInYears } from "date-fns";

import { MagnifyingGlassIcon, MegaphoneIcon } from "@heroicons/vue/24/outline";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
defineProps({
    staff: Object,
});

let addContact = () => {
    showAddContactModal.value = true;
    // console.log("add dependent");
};
let showDeleteDepModal = ref(false);
let dependentToDelete = ref(null);
let showAddContactModal = ref(false);
let deleteDependents = (id) => {
    dependentToDelete.value = id;
    showDeleteDepModal.value = true;
};
let editDependent = (id) => {
    console.log("edit dependent " + id);
};

const formattedDob = (dateString) => {
    const date = new Date(dateString);
    return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString) => {
    const date = new Date(dateString);
    return differenceInYears(new Date(), date);
};

let showPerson = (id) => {
    Inertia.get(route("person.show", { person: id }));
};
</script>
<template>
    <div class="overflow-hidden bg-white shadow sm:rounded-lg w-full mx-auto">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
                Contacts
            </h3>
        </div>

        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <div
                class="flex justify-end items-center px-4 bg-white dark:bg-gray-800"
            >
                <button
                    @click.stop.prevent="addContact"
                    type="button"
                    class="text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 inline-flex items-center"
                >
                    <MegaphoneIcon class="w-5 h-5 mr-2" />
                    Add Contact
                </button>
            </div>
            <table
                class="w-full text-sm text-left text-gray-500 dark:text-gray-400"
            >
                <thead
                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
                >
                    <tr>
                        <th scope="col" class="py-3 px-6">Name</th>
                        <th scope="col" class="py-3 px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                        <th
                            scope="row"
                            class="flex items-center py-4 px-6 text-gray-900 whitespace-nowrap dark:text-white hover:cursor-pointer"
                        >
                            0205588666
                        </th>

                        <td class="py-4 px-6 text-right space-x-3">
                            <!-- Modal toggle -->
                            <button
                                @click.prevent="addContact"
                                type="button"
                                class="font-medium text-green-600 dark:text-green-500 hover:underline"
                            >
                                Edit
                            </button>
                            <button
                                @click.prevent="
                                    deleteDependents({
                                        id: person.id,
                                        name: person.name,
                                        staff: staff.name,
                                    })
                                "
                                type="button"
                                class="font-medium text-red-600 dark:text-red-500 hover:underline"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <!-- <DeleteContactsModal
                :dependent="dependentToDelete"
                @closeModal="showDeleteDepModal = false"
                :isVisible="showDeleteDepModal"
            /> -->
            <AddContactsModal
                :staff="staff"
                @closeModal="showAddContactModal = false"
                :isVisible="showAddContactModal"
            />
        </div>
    </div>
</template>
