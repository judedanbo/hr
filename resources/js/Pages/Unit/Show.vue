<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { BadgeCheckIcon, SearchIcon } from "@heroicons/vue/outline";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import BreezeInput from "@/Components/Input.vue";

let props = defineProps({
    unit: Object,
    filters: Object,
});

//
let BreadcrumbLinks = [
    { name: "Institutions", url: route("institution.index") },
    {
        name: props.unit.institution.name,
        url: route("institution.show", {
            institution: props.unit.institution.id,
        }),
    },
    {
        name: props.unit.parent != null ? props.unit.parent.name : null,
        url: route("unit.show", {
            unit: props.unit.parent != null ? props.unit.parent.id : 99999, //use 99999 and unit id if the unit has no parent
        }),
    },
    { name: props.unit.name },
];
</script>

<template>
    <Head title="Dashboard" />

    <MainLayout>
        <template #header>
            <BreadCrumpVue :links="BreadcrumbLinks" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ props.unit.type }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div
                        class="p-10 bg-white border-b border-gray-200 md:flex justify-around"
                    >
                        <div class="flex flex-col md:flex-row items-center">
                            <div class="p-8 ml-6">
                                <h1
                                    class="text-3xl lg:text-4xl font-bold tracking-wider text-gray-700"
                                >
                                    {{ props.unit.name }}
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-if="props.unit.type != 'Unit'"
                    class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 mt-4 w-full lg:w-2/5"
                >
                    <p
                        class="font-bold text-xl px-8 pt-8 text-gray-700 dark:text-white tracking-wide"
                    >
                        <span v-if="props.unit.type == 'Department'">
                            Divisions
                        </span>
                        <!-- <span v-else-if="props.unit.type == 'Division'">Units</span> -->
                        <span v-else>Units</span>

                        <span
                            class="text-lg text-gray-500 dark:text-white ml-2"
                        >
                            ({{ props.unit.subs.length }})
                        </span>
                    </p>

                    <div class="mt-1 relative mx-8">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <span class="text-gray-500 sm:text-sm">
                                <SearchIcon class="w-4 h-4" />
                            </span>
                        </div>
                        <BreezeInput
                            type="search"
                            class="w-full pl-8 bg-slate-100 border-0"
                            required
                            autofocus
                            placeholder="Search departments..."
                        />
                    </div>

                    <ul class="px-8 pb-6">
                        <li
                            v-for="(subUnit, index) in props.unit.subs"
                            :key="index"
                            class="flex items-center text-gray-600 dark:text-gray-200 justify-between py-4 px-4 rounded-xl hover:bg-slate-200"
                        >
                            <div
                                class="flex items-center justify-start text-lg"
                            >
                                <span class="mr-4"> {{ index + 1 }} </span>
                                <div class="flex flex-col">
                                    <Link
                                        :href="
                                            route('unit.show', {
                                                unit: subUnit.id,
                                            })
                                        "
                                        class="font-semibold"
                                    >
                                        {{ subUnit.name }}
                                    </Link>
                                    <div class="flex justify-start space-x-4">
                                        <span
                                            v-if="
                                                props.unit.type == 'Department'
                                            "
                                            class="text-sm"
                                        >
                                            Units:
                                            {{ subUnit.subs }}
                                        </span>
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
