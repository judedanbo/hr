<script setup>
defineProps({
    staff: Object,
    person: Object,
});
</script>
<template>
    <div class="px-4 py-5 sm:px-6 bg-white dark:bg-gray-600">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-50">
            Personal Information
        </h3>
        <p class="my-2 max-w-2xl text-sm text-gray-500 dark:text-gray-100">
            Personal details staff.
        </p>
        <div class="border-t border-gray-200 ">
            <dl>
                <div
                    class="odd:bg-white dark:odd:bg-gray-600 even:bg-slate-50 dark:even:bg-dark-500 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-50">
                        Identifications
                    </dt>
                    <dd
                        v-for="ids in person.identities"
                        :key="ids.number"
                        class="mt-1 text-sm text-gray-900 dark:text-gray-50 sm:col-span-2 sm:mt-0"
                    >
                        <span class="">{{ ids.type }}: </span>
                        <span class="font-semibold tracking-wider">{{
                            ids.number
                        }}</span>
                    </dd>
                </div>
                <div
                    v-if="person.national_id_number"
                    class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500">
                        Ghana Card No
                    </dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                    ></dd>
                </div>

                <div
                    class="evenbg-slate-50 dark:even:bg-dark-500 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">
                        Nationality
                    </dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0"
                    >
                        {{ person.nationality ?? "Ghanaian" }}
                    </dd>
                </div>
                <div
                    class=" even:bg-slate-50 dark:even:bg-dark-500 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Religion</dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0"
                    >
                        {{ person.religion ?? "Not Provided" }}
                    </dd>
                </div>
                <div
                    class=" px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                >
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">
                        Marital Status
                    </dt>
                    <dd
                        class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0"
                    >
                        {{ person.marital_status ?? "Not Provided" }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>
