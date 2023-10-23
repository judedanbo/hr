<script setup>
import { format, differenceInYears } from "date-fns";
import { Link } from "@inertiajs/inertia-vue3";
import AddQualification from "./partials/AddQualification.vue";
import Modal from "@/Components/NewModal.vue";
import NotesDetails from "./NotesDetails.vue";
import { ref } from "vue";
import { useToggle } from "@vueuse/core";
import NewNote from "./NewNote.vue";

defineProps({
    notes: Array,
    notable_type: String,
    notable_id: Number,
    user: Object,
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
</script>
<template>
    <!-- Notes on staff -->
    <main>
        <h2 class="sr-only">Notes on Staff</h2>
        <div
            class="py-6 rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
        >
            <dl class="flex flex-wrap">
                <div class="flex-auto pl-6">
                    <dt
                        class="text-xl font-semibold leading-6 text-gray-900 dark:text-gray-50"
                    >
                        Notes
                    </dt>
                </div>

                <div class="-mx-4 flow-root sm:mx-0 w-full px-4">
                    <NewNote
                        :notable_id="notable_id"
                        :notable_type="notable_type"
                        :user="user"
                    />
                    <NotesDetails :notes="notes" />
                    <!-- <div v-else class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center ">No notes found.</div> -->
                </div>
            </dl>
        </div>
        <!-- <Modal @close="toggleQualificationModal()" :show="openQualificationModal">
      <AddQualification @formSubmitted="toggleQualificationModal()"  :person="person" />
    </Modal> -->
    </main>
</template>
