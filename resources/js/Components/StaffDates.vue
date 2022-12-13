<script setup>
import { computed } from "vue";
import {
    format,
    formatDistance,
    formatDistanceToNowStrict,
    formatRelative,
    differenceInYears,
    differenceInMonths,
    addYears,
    differenceInDays,
} from "date-fns";

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
    <div class="overflow-hidden bg-white shadow sm:rounded-lg w-full">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
                Important Dates
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Important dates of staff.
            </p>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div
                    class="odd:bg-white even:bg-slate-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500">
                        Date of Birth / Age
                    </dt>
                    <dd class="mt-1 text-gray-900 sm:col-span-2 sm:mt-0">
                        {{ formatDate(getDate(person.dob)) }}
                        <div class="text-sm">
                            (
                            {{ getDifference(person.dob).years }}
                            years
                            {{
                                getDifference(person.dob).months
                                    ? getDifference(person.dob).months +
                                      " months"
                                    : ""
                            }})
                        </div>
                    </dd>
                </div>
                <div
                    v-if="staff.hire_date"
                    class="odd:bg-white even:bg-slate-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500">
                        Date Employed
                    </dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                    >
                        {{ formatDate(getDate(staff.hire_date)) }}
                        <div class="text-sm">
                            (
                            {{ getDifference(staff.hire_date).years }}
                            years
                            {{
                                getDifference(staff.hire_date).months
                                    ? getDifference(staff.hire_date).months +
                                      " months"
                                    : ""
                            }})
                        </div>
                    </dd>
                </div>

                <div
                    class="odd:bg-white even:bg-slate-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500">
                        Current Rank / Since
                    </dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                    >
                        {{ staff.current_job }}
                        <div class="text-sm">
                            (
                            {{ getDifference(staff.start_date).years }}
                            years
                            {{
                                getDifference(staff.start_date).months
                                    ? getDifference(staff.start_date).months +
                                      " months"
                                    : ""
                            }})
                        </div>
                    </dd>
                </div>
                <div
                    class="odd:bg-white even:bg-slate-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500">
                        Retirement
                    </dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                    >
                        <!-- {{ getRetired(person.dob) }} -->
                        {{ formatDate(getRetirementDate(person.dob)) }}
                        <div class="text-sm">
                            (
                            {{
                                getDifference(getRetirementDate(person.dob))
                                    .years
                            }}
                            years
                            {{
                                getDifference(getRetirementDate(person.dob))
                                    .months
                                    ? getDifference(
                                          getRetirementDate(person.dob)
                                      ).months + " months"
                                    : ""
                            }})
                        </div>
                    </dd>
                </div>
                <div
                    class="odd:bg-white even:bg-slate-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500">
                        Current Unit
                    </dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                    >
                        {{ staff.unit.name }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>
