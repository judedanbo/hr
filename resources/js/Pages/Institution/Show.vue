<script setup>
import NewLayout from "@/Layouts/NewAuthenticated.vue";
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
import NoItem from "@/Components/NoItem.vue";
import PageHeader from "@/Components/PageHeader.vue";

let props = defineProps({
    institution: Object,
    departments: Array,
    filters: Object,
});

//
// let BreadcrumbLinks = [
//     { name: "Institutions", url: route("institution.index") },
//     { name: props.institution.name },
// ];

let search = ref(props.filters.search);

watch(
    search,
    debounce(function (value) {
        Inertia.get(
            route("institution.show", {
                institution: props.institution.id,
            }),
            { search: value },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);
</script>

<template>
    <Head v-if="institution" :title="institution.name" />

    <NewLayout>
        <template #header>
            <PageHeader v-if="institution" :name="institution.name" />

        </template>

        <div v-if="institution" class="mx-auto flex flex-wrap gap-4 justify-center mt-2">
            <div class="flex gap-4 w-full justify-center">
                <InfoCard v-if="institution?.staff > 0" title="Staff" :value="institution.staff" :link="route('institution.staffs', {
                        institution: institution.id,
                    })
                    " />
                <InfoCard v-if="institution?.department > 0" :link="route('unit.index', {
                        institution: institution.id,
                    })
                    " title="Department" :value="institution.departments" />
                <InfoCard v-if="institution?.divisions > 0" title="Divisions" :value="institution.divisions" />
                <InfoCard v-if="institution?.units > 0" title="Units" :value="institution.units" />
            </div>

            <div v-if="departments" class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 mt-4 w-full lg:w-1/2">
                <p class="font-bold text-xl px-8 pt-8 text-gray-700 dark:text-white tracking-wide">
                    Departments
                    <span class="text-lg text-gray-500 dark:text-white ml-2">
                        ({{ departments.length }})
                    </span>
                </p>

                <div class="mt-1 relative mx-8">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">
                            <MagnifyingGlassIcon class="w-4 h-4" />
                        </span>
                    </div>

                    <BreezeInput v-model="search" type="search" class="w-full pl-8 bg-slate-100 border-0" required autofocus
                        placeholder="Search departments..." />
                </div>

                <ul class="px-8 pb-6 max-h-96 overflow-y-auto">
                    <li v-for="(department, index) in departments" :key="index"
                        class="flex items-center text-gray-600 dark:text-gray-200 justify-between py-4 px-4 rounded-xl hover:bg-slate-200">
                        <div class="flex items-center justify-start text-lg">
                            <span class="mr-4"> {{ index + 1 }} </span>
                            <div class="flex flex-col">
                                <Link :href="route('unit.show', {
                                    unit: department.id,
                                })
                                    " class="font-semibold">
                                {{ department.name }}
                                </Link>
                                <div class="flex justify-start space-x-4">
                                    <span class="text-sm">
                                        Divisions:
                                        {{ department.divisions ?? 0 }}
                                    </span>
                                    <span class="text-sm">
                                        Units: {{ department.units ?? 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div v-else class="w-1/2 flex justify-center items-center rounded shadow py-10 bg-white">
                No Units / Departments
            </div>
        </div>
        <NoItem v-else name="Institution" />
    </NewLayout>
</template>
