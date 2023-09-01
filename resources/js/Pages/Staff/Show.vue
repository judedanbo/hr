<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import StaffRanks from "./StaffRanks.vue";
import StaffUnits from "./StaffUnits.vue";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import StaffPersonalInfo from "@/Components/StaffPersonalInfo.vue";
import StaffDates from "@/Pages/Staff/StaffDates.vue";
import StaffDependents from "@/Components/StaffDependents.vue";


// import { PaperClipIcon } from '@heroicons/vue/20/solid'

const formattedDob = (dateString) => {
    const date = new Date(dateString);
    return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString) => {
    const date = new Date(dateString);
    return differenceInYears(new Date(), date);
};

let props = defineProps({
    person: Object,
    staff: Object,
    contact_types: Array,
    address: Object,
    contacts: Array,
    filters: Object,
});

let tabs = [
    { name: "Profile", active: true },
    { name: "Account" },
    { name: "Notification" },
];
let BreadcrumbLinks = [
    { name: "Staff", url: "/staff" },
    { name: props.person.name, url: "/" },
];
</script>

<template>
    <Head :title="person.name" />

    <MainLayout>
        <div class="py-2">
            <BreadCrumpVue :links="BreadcrumbLinks" />
            <div class="max-w-7xl mx-auto md:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-600 overflow-hidden shadow-sm md:rounded-lg">
                    <div class="px-4 md:px-0 bg-white dark:bg-gray-600 border-b border-gray-200 dark:border-gray-800 md:flex justify-around md:justify-start">
                        <div class="flex flex-col md:flex-row items-center justify-center">
                            <div
                                class="w-48 h-48 md:w-80 md:h-full rounded-full md:rounded-none bg-gray-400 flex justify-center items-center">
                                <h1 class="text-white font-semibold text-6xl md:text-7xl tracking-widest">
                                    {{ person.initials }}
                                </h1>
                            </div>
                            <div class="pt-8 w-full md:p-8">
                                <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold tracking-wider text-gray-700 dark:text-gray-200">
                                    {{ person.name }}
                                </h1>

                                <p class="text-sm md:text-lg font-bold">
                                    <!-- {{ staff.unit.name }} -->
                                </p>
                                <p class="text-lg md:text-xl text-gray-500 dark:gray-text-100 py-4">
                                    {{ staff.current_job }}
                                </p>
                                <div class="lg:flex space-y-8 lg:space-y-0 justify-between">
                                    <div>
                                        <p class="text-lg md:text-xl dark:text-gray-100">
                                            {{ person.gender }}
                                        </p>
                                        <p class="text-lg md:text-xl dark:text-gray-100">
                                            Born:
                                            {{ formattedDob(person.dob) }}
                                        </p>
                                        <p class="pl-14 text-sm dark:text-gray-100">
                                            {{
                                                getAge(person.dob) +
                                                " years old"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-lg md:text-xl dark:text-gray-100" :title="getAge(staff.hire_date) +
                                                ' years employed'
                                                ">
                                            Employed:
                                            {{ formattedDob(staff.hire_date) }}
                                        </p>
                                        <p class="text-lg md:text-xl dark:text-gray-100">
                                            File No.: {{ staff.file_number }}
                                        </p>
                                        <p class="text-lg md:text-xl dark:text-gray-100">
                                            Staff No.: {{ staff.staff_number }}
                                        </p>
                                    </div>
                                </div>

                                <!-- <Link
                                    as="button"
                                    :href="'mailto:' + staff.email"
                                    :title="staff.email"
                                    class="mt-8 py-2.5 px-5 mr-2 text-sm md:text-lg font-medium text-gray-900 bg-white rounded-lg border border-green-50 hover:bg-gray-100 hover:text-green-700 focus:z-10 focus:ring-1 focus:outline-none focus:ring-green-700 focus:text-green-700 dark:bg-gray-800 dark:text-gray-400 dark:border-green-600 dark:hover:text-white dark:hover:bg-gray-700 inline-flex items-center"
                                >
                                    <EnvelopeIcon class="w-5 h-5 mr-2" />
                                    Email
                                </Link> -->

                                <div class="flex items-center justify-around">
                                    <!-- <p
                                        class="text-lg"
                                        :title="
                                            'born ' + formattedDob(staff.dob)
                                        "
                                    >
                                        Age: {{ getAge(staff.dob) }} years
                                    </p> -->
                                    <!-- <p
                                        class="text-lg"
                                        :title="
                                            getAge(staff.hire_date) +
                                            ' years employed'
                                        "
                                    >
                                        Employed:
                                        {{ formattedDob(staff.hire_date) }}
                                    </p> -->
                                </div>

                                <!-- <p class="text-lg tracking-wide">
                                    SSNIT No. {{ staff.ssn }}
                                </p> -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex mt-8 flex-wrap justify-center md:justify-between space-y-8 md:space-y-0 items-start">
                    <div class="flex gap-x-8 gap-y-4 flex-wrap shadow sm:rounded-lg w-full justify-center items-start">
                        <StaffDates :staff="staff" :person="person" class="w-1/2" />
                        <StaffPersonalInfo :staff="staff" :person="person" class="w-1/3" />
                        <StaffRanks :ranks="staff.ranks" class="w-1/3" />
                        <StaffUnits :ranks="staff.units" class="w-1/3" />

                        <!-- <StaffDependents v-if="staff" :staff="staff" class="w-1/2" /> -->
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
