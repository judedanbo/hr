<script setup>
import {
    format,
    differenceInYears,
    differenceInMonths,
    addYears,
    isDate,
    formatDistance
} from "date-fns";
import { addSeconds } from "date-fns/esm";

defineProps({
    staff: Object,
    person: Object,
});

const getDate = (dateString) => {
    return new Date(dateString);
    // return formatRelative(date, new Date(), { addSuffix: true });
};

const getDifference = (dateString, now = new Date()) => {
    const date = new Date(dateString);
    if (!isDate(now)) {
        now = new Date(now);
    }
    let years = Math.abs(differenceInYears(date, now));
    let months = Math.abs(differenceInMonths(date, now)) - years * 12;
    return { years, months };
};
const formatDate = (date) => {
    return format(date, "dd MMMM, yyyy");
};

// const getRetired = computed((dateString) => {
//     const date = new Date(dateString);
//     return differenceInDays(date, new Date());
// });

let getAge = (dateString) => {
    const date = new Date(dateString);
    return differenceInYears(new Date(), date);
};
let getMonth = (dateString) => {
    const date = new Date(dateString);
    return differenceInMonths(new Date(), date);
};
let getRetirementDate = (dateString) => {
    const date = new Date(dateString);
    const retirement_date = addYears(date, 60);
    return retirement_date;
};
</script>
<template>
    <div class="px-4 py-5 sm:px-6 bg-white dark:bg-gray-600">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
            Important Information
        </h3>
        <p class="my-2 max-w-2xl text-sm text-gray-500 dark:text-gray-200">
            Important dates of staff.
        </p>
        <div class="border-t border-gray-200">
            <dl>
                <div
                    class= "px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-200">Status</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-200 sm:col-span-2 sm:mt-0">
                        {{ staff.statuses[0].status }}
                        <dd v-if="staff.statuses[0].end_date" class="mt-1 text-gray-900 dark:text-gray-200 sm:col-span-2 sm:mt-0">
                            {{ staff.statuses[0].start_date }} - {{ staff.statuses[0].end_date }}
                        </dd>
                        <dd class="mt-1 text-gray-900 dark:text-gray-200 sm:col-span-2 sm:mt-0">
                            {{ staff.statuses[0].description }}
                        </dd>
                    </dd>
                </div>
                <div
                    class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-200">
                        Date of Birth / Age
                    </dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-200 sm:col-span-2 sm:mt-0">
                        {{ formatDate(getDate(person.dob)) }}
                        <div class="text-sm">

                            {{ getDifference(person.dob).years }}
                            years old

                        </div>
                    </dd>
                </div>
                <div
                    v-if="staff.hire_date"
                    class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-200">Date Employed</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200 sm:col-span-2 sm:mt-0">
                        {{ formatDate(getDate(staff.hire_date)) }}
                        <div class="text-sm">
                            {{ formatDistance(new Date(staff.hire_date), new Date(), {addSuffix: true}) }}

                        </div>
                    </dd>
                </div>
                <div
                    class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-200">Retirement</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200 sm:col-span-2 sm:mt-0">
                        <!-- {{ getRetired(person.dob) }} -->
                        {{ formatDate(getRetirementDate(person.dob)) }}
                        <div class="text-sm">
                            {{ formatDistance(new Date(getRetirementDate(person.dob)), new Date(), {addSuffix: true}) }}
                        </div>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>
