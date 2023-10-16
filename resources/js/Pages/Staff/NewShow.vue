<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { formatDistance } from "date-fns";
import StaffDates from "./StaffDates.vue";
import Summary from "@/Pages/Person/Summary.vue";
import PromotionHistory from "./PromotionHistory.vue";
import TransferHistory from "./TransferHistory.vue";
import StatusHistory from "./StatusHistory.vue";
import StaffType from "../StaffType/Index.vue";
import Qualifications from "./Qualifications.vue";
import Dependents from "@/Pages/StaffDependents/Index.vue";
// import Dependents from "./Dependents.vue";
import Address from "./Address.vue";
import Notes from "./Notes.vue";
import { useToggle } from "@vueuse/core";
import { ref } from "vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import {
    EllipsisVerticalIcon,
    PlusIcon,
    EllipsisHorizontalIcon,
} from "@heroicons/vue/20/solid";
import Avatar from "../Person/partials/Avatar.vue";
import NewModal from "@/Components/NewModal.vue";
import EditStaffForm from "./EditStaffForm.vue";

let showPromotionForm = ref(false);
let showTransferForm = ref(false);
let openEditModal = ref(false);

let toggle = useToggle(openEditModal);

let togglePromotionForm = useToggle(showPromotionForm);
let toggleTransferForm = useToggle(showTransferForm);
const formattedDob = (dob) => {
    if (!dob) return "";
    return new Date(dob).toLocaleDateString("en-GB", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
};

let props = defineProps({
    user: Object,
    person: Object,
    staff: Object,
    contact_types: Array,
    address: Object,
    contacts: Array,
    qualifications: Array,
    filters: Object,
});

let BreadcrumbLinks = [
    { name: "Staff", url: "/staff" },
    { name: props.person.name, url: "/" },
];
</script>
<template>
    <Head :title="person.name" />

    <MainLayout>
        <main>
            <header
                class="relative isolate pt-4 border dark:border-gray-600 rounded-lg"
            >
                <div
                    class="absolute inset-0 -z-10 overflow-hidden"
                    aria-hidden="true"
                >
                    <div
                        class="absolute left-16 top-full -mt-16 transform-gpu opacity-50 blur-3xl xl:left-1/2 xl:-ml-80"
                    >
                        <div
                            class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-green-100 dark:from-gray-100 to-yellow-200 dark:to-gray-800 dark:border-gray-700 rounded-3xl"
                            style="
                                clip-path: polygon(
                                    100% 38.5%,
                                    82.6% 100%,
                                    60.2% 37.7%,
                                    52.4% 32.1%,
                                    47.5% 41.8%,
                                    45.2% 65.6%,
                                    27.5% 23.4%,
                                    0.1% 35.3%,
                                    17.9% 0%,
                                    27.7% 23.4%,
                                    76.2% 2.5%,
                                    74.2% 56%,
                                    100% 38.5%
                                );
                            "
                        />
                    </div>
                    <div
                        class="absolute inset-x-0 bottom-0 h-px bg-gray-900/5"
                    />
                </div>

                <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div
                        class="mx-auto flex flex-wrap max-w-2xl items-center md:justify-between lg:justify-start gap-x-8 lg:mx-0 lg:max-w-none"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between md:justify-start gap-x-6 w-full md:w-1/2"
                        >
                            <Avatar
                                v-if="person.image"
                                :initials="person.initials"
                                :image="person.image"
                                class="w-24 h-24"
                            />
                            <div
                                v-else
                                class="flex justify-center items-center h-24 w-24 flex-none rounded-lg ring-1 ring-green-400/60 dark:ring-gray-400 text-5xl text-green-400/50 dark:text-gray-300 font-bold tracking-wide"
                            >
                                {{ person.initials }}
                            </div>
                            <div class="">
                                <div
                                    class="text-sm leading-6 text-gray-500 dark:text-gray-300"
                                >
                                    File Number
                                    <span
                                        class="text-gray-700 dark:text-gray-100"
                                        >{{ staff.file_number }}</span
                                    >
                                </div>
                                <div
                                    class="text-sm leading-6 text-gray-500 dark:text-gray-300"
                                >
                                    Staff Number
                                    <span
                                        class="text-gray-700 dark:text-gray-100"
                                        >{{ staff.staff_number }}</span
                                    >
                                </div>
                                <div
                                    class="mt-1 text-xl font-semibold leading-6 text-gray-900 dark:text-white"
                                >
                                    {{ person.name }}
                                </div>
                            </div>
                        </div>
                        <div>
                            <div
                                v-if="staff.statuses.length > 0"
                                class="mt-4 md:mt-0"
                            >
                                <div
                                    class="text-sm leading-6 text-gray-500 dark:text-gray-300"
                                >
                                    Current Status
                                    <span
                                        class="text-gray-700 dark:text-gray-100"
                                        >{{ staff.statuses[0]?.status }}</span
                                    >
                                </div>
                                <div
                                    class="text-sm leading-6 text-gray-500 dark:text-gray-300"
                                >
                                    Description
                                    <span
                                        class="text-gray-700 dark:text-gray-100"
                                        >{{
                                            staff.statuses[0]?.description
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="text-sm leading-6 text-gray-500 dark:text-gray-300"
                                >
                                    Start date
                                    <span
                                        class="text-gray-700 dark:text-gray-100"
                                        >{{
                                            staff.statuses[0]?.start_date
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-x-4 sm:gap-x-6 justify-between w-full md:w-fit"
                        >
                            <button
                                @click="togglePromotionForm()"
                                type="button"
                                class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
                            >
                                Promote
                            </button>
                            <button
                                @click="toggleTransferForm()"
                                type="button"
                                class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
                            >
                                Transfer
                            </button>

                            <a
                                @click.prevent="toggle()"
                                href="#"
                                class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
                            >
                                <PlusIcon
                                    class="-ml-1.5 h-5 w-5"
                                    aria-hidden="true"
                                />
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl py-4 xl:px-8">
                <div
                    class="mx-auto lg:grid max-w-2xl grid-cols-1 grid-rows-1 items-start gap-x-8 gap-y-8 lg:mx-0 lg:max-w-none lg:grid-cols-3"
                >
                    <div class="md:col-start-3 flex flex-wrap gap-4 w-full">
                        <!-- Employment summary -->
                        <Summary :person="person" />
                        <!-- Contact information -->
                        <Address
                            :address="address"
                            :contacts="contacts"
                            :contact_types="contact_types"
                            :person="person.id"
                        />
                        <!-- TODO Add dependant forme and display -->
                        <Dependents
                            :staff_id="staff.staff_id"
                            :dependents="staff.dependents"
                        />
                        <!-- <StaffDependents v-if="staff" :staff="staff" class="" /> -->
                    </div>

                    <div
                        class="col-start-1 col-span-3 lg:col-span-2 lg:row-span-2 lg:row-end-2 flex flex-wrap gap-4 items-start"
                    >
                        <!-- important Dates -->
                        <StaffDates class="w-2/3" :staff="staff" />
                        <!-- statutes history -->
                        <div class="w-full md:flex-1 flex-1 space-y-2">
                            <StatusHistory
                                @close-form="toggleTransferForm()"
                                :statuses="staff.statuses"
                                :staff="staff.staff_id"
                                :institution="staff.institution_id"
                                class=""
                            />
                            <StaffType
                                @close-form="toggleTransferForm()"
                                :types="staff.staff_type"
                                :staff="{
                                    id: staff.staff_id,
                                    hire_date: staff.hire_date,
                                }"
                                :institution="staff.institution_id"
                            />
                        </div>
                        <!-- Qualifications -->
                        <Qualifications
                            class="w-full"
                            :qualifications="qualifications"
                            :person="{
                                id: person.id,
                                name: person.name,
                            }"
                        />
                        <!-- Qualifications -->
                        <Notes
                            class="w-full"
                            :notes="staff.notes"
                            notable_type="App\Models\InstitutionPerson"
                            :notable_id="staff.staff_id"
                            :user="user"
                        />
                        <!-- Employment History -->
                        <PromotionHistory
                            @close-form="togglePromotionForm()"
                            :promotions="staff.ranks"
                            :staff="staff.staff_id"
                            :institution="staff.institution_id"
                            :showPromotionForm="showPromotionForm"
                            class="w-full md:flex-1"
                        />
                        <!-- Posting History -->
                        <TransferHistory
                            @close-form="toggleTransferForm()"
                            :transfers="staff.units"
                            :staff="staff.staff_id"
                            :staffName="person.name"
                            :institution="staff.institution_id"
                            :showTransferForm="showTransferForm"
                            class="w-full md:flex-1 flex-1"
                        />
                    </div>
                </div>
            </div>
            <NewModal @close="toggle()" :show="openEditModal">
                <EditStaffForm
                    @formSubmitted="toggle()"
                    :staff_id="staff.staff_id"
                />
            </NewModal>
        </main>
    </MainLayout>
</template>
