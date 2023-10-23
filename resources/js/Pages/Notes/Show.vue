<script setup>
import { format, differenceInYears } from "date-fns";
import { Link } from "@inertiajs/inertia-vue3";
// import AddQualification  from "./partials/AddQualification.vue";
import Modal from "@/Components/NewModal.vue";
import { ref } from "vue";
import { useToggle } from "@vueuse/core";

defineProps({
    qualifications: Array,
    person: Number,
});

let openQualificationModal = ref(false);
let toggleQualificationModal = useToggle(openQualificationModal);

const formattedDob = (dob) => {
    if (!dob) return "";
    return new Date(dob).toLocaleDateString("en-GB", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
};

let getAge = (dateString) => {
    const date = new Date(dateString);
    return differenceInYears(new Date(), date);
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

                <div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
                    <table v-if="qualifications.length > 0" class="min-w-full">
                        <colgroup></colgroup>
                        <thead
                            class="border-b border-gray-300 dark:border-gray-200/50 text-gray-900 dark:text-gray-50"
                        >
                            <tr>
                                <th
                                    scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
                                >
                                    Institution
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
                                >
                                    Level
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
                                >
                                    Course
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
                                >
                                    Qualification
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
                                >
                                    Year
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="qualification in qualifications"
                                :key="qualification.id"
                                class="border-b border-gray-200 dark:border-gray-400/30"
                            >
                                <td
                                    class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0"
                                >
                                    <div
                                        class="font-medium text-gray-900 dark:text-gray-50"
                                    >
                                        {{ qualification.institution }}
                                    </div>
                                </td>
                                <td
                                    class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
                                >
                                    {{ qualification.level }}
                                </td>
                                <td
                                    class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
                                >
                                    {{ qualification.course }}
                                </td>

                                <td
                                    class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
                                >
                                    {{ qualification.qualification }}
                                    <div
                                        class="font-medium text-xs text-gray-900 dark:text-gray-50"
                                    >
                                        {{ qualification.qualification_number }}
                                    </div>
                                </td>
                                <td
                                    class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
                                >
                                    {{ qualification.year }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div
                        v-else
                        class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
                    >
                        No qualifications found.
                    </div>
                </div>
            </dl>
        </div>
        <Modal
            @close="toggleQualificationModal()"
            :show="openQualificationModal"
        >
            <!-- <AddQualification @formSubmitted="toggleQualificationModal()"  :person="person" /> -->
        </Modal>
    </main>
</template>
