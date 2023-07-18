<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { Inertia } from "@inertiajs/inertia";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import InfoCard from "@/Components/InfoCard.vue";

let props = defineProps({
    job: Object,

    filters: Object,
});

//
let BreadcrumbLinks = [
    { name: "Job", url: route("job.index") },
    { name: props.job.name },
];

let search = ref(props.filters.search);

// watch(
//     search,
//     debounce(function (value) {
//         Inertia.get(
//             route("institution.show", {
//                 institution: props.institution.id,
//             }),
//             { search: value },
//             { preserveState: true, replace: true, preserveScroll: true }
//         );
//     }, 300)
// );
</script>

<template>
    <Head :title="job.name" />

    <MainLayout>
        <template #header>
            <!-- <BreadCrumpVue :links="BreadcrumbLinks" /> -->
            <!-- <PageHeader v-if="job" :name="job.name" /> -->
        </template>

        <div class="py-2">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 my-6 md:grid-cols-2 lg:grid-cols-4 bg-w">
                    <InfoCard title="Staff" :value="job.staff_count" link="#" />
                </div>

                <div v-if="job.staff" class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 mt-4 w-full lg:w-2/5">
                    <p class="font-bold text-xl px-8 pt-8 text-gray-700 dark:text-white tracking-wide">
                        Staff
                        <!-- <span
                            class="text-lg text-gray-500 dark:text-white ml-2"
                        >
                            ({{ staff.length }})
                        </span> -->
                    </p>

                    <div class="mt-1 relative mx-8">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">
                                <MagnifyingGlassIcon class="w-4 h-4" />
                            </span>
                        </div>
                        <BreezeInput v-model="search" type="search" class="w-full pl-8 bg-slate-100 border-0" required
                            autofocus placeholder="Search staff..." />
                    </div>

                    <ul class="px-8 pb-6 max-h-96 overflow-y-auto">
                        <li v-for="(sta, index) in job.staff" :key="index"
                            class="flex items-center text-gray-600 dark:text-gray-200 justify-between py-4 px-4 rounded-xl hover:bg-slate-200">
                            <div class="flex items-center justify-start text-lg">
                                <span class="mr-4"> {{ index + 1 }} </span>
                                <div class="flex flex-col">
                                    <Link :href="route('staff.show', {
                                        staff: sta.id,
                                    })
                                        " class="font-semibold">
                                    {{ sta.name }}
                                    </Link>
                                    <div class="flex justify-start space-x-4">
                                        <span class="text-sm">
                                            {{ sta.staff_number }}
                                        </span>
                                        <Link v-if="sta.unit_id" :href="route('unit.show', {
                                                unit: sta.unit_id,
                                            })
                                            " class="text-sm">
                                        {{ sta.unit }}</Link>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
