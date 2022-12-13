<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";

import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";

let props = defineProps({
    unit: Object,
    filters: Object,
});

let dept = ref(props.filters.dept);
let staff = ref(props.filters.staff);

watch(
    dept,
    debounce(function (value) {
        Inertia.get(
            route("unit.show", {
                unit: props.unit.id,
            }),
            { dept: value },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);
watch(
    staff,
    debounce(function (value) {
        Inertia.get(
            route("unit.show", {
                unit: props.unit.id,
            }),
            { staff: value },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);

let num = 1;

//
let BreadcrumbLinks = [
    {
        name: props.unit.institution.name,
        url: route("institution.show", {
            institution: props.unit.institution.id,
        }),
    },
    {
        name: "Departments",
        url: route("unit.index", {
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ props.unit.name }}
            </h2>
        </template>

        <div class="py-2">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <BreadCrumpVue :links="BreadcrumbLinks" />
                <div class="flex space-x-4 items-start">
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
                                    <MagnifyingGlassIcon class="w-4 h-4" />
                                </span>
                            </div>
                            <BreezeInput
                                v-model="dept"
                                type="search"
                                class="w-full pl-8 bg-slate-100 border-0"
                                required
                                autofocus
                                :placeholder="
                                    props.unit.type == 'Department'
                                        ? 'Search divisions...'
                                        : 'Search units...'
                                "
                            />
                        </div>

                        <ul class="px-8 pb-6 max-h-96 overflow-y-auto">
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
                                            class=""
                                        >
                                            {{ subUnit.name }}
                                        </Link>
                                        <div
                                            class="flex justify-start space-x-4"
                                        >
                                            <span
                                                v-if="
                                                    props.unit.type ==
                                                    'Department'
                                                "
                                                class="text-sm"
                                            >
                                                Units:
                                                {{ subUnit.subs }}
                                            </span>
                                            <span class="text-sm">
                                                Staff:
                                                {{ subUnit.staff_count }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div
                        class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 mt-4 w-full lg:w-2/5"
                    >
                        <p
                            class="font-bold text-xl px-8 pt-8 text-gray-700 dark:text-white tracking-wide"
                        >
                            <span>Staff</span>

                            <span
                                class="text-lg text-gray-500 dark:text-white ml-2"
                            >
                                ({{ props.unit.staff_number }})
                            </span>
                        </p>

                        <div class="mt-1 relative mx-8">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <span class="text-gray-500 sm:text-sm">
                                    <MagnifyingGlassIcon class="w-4 h-4" />
                                </span>
                            </div>
                            <BreezeInput
                                v-model="staff"
                                type="search"
                                class="w-full pl-8 bg-slate-100 border-0"
                                required
                                autofocus
                                placeholder="Search staff..."
                            />
                        </div>

                        <ul class="px-8 pb-6 max-h-96 overflow-y-auto">
                            <li
                                v-for="(st, index) in props.unit.staff"
                                :key="index"
                                class="flex items-center text-gray-600 dark:text-gray-200 justify-between py-4 px-4 rounded-xl hover:bg-slate-200"
                            >
                                <div
                                    class="flex items-center justify-start text-lg"
                                >
                                    <span class="mr-4"> {{ index + 1 }} </span>
                                    <div class="flex flex-col">
                                        <p class="">
                                            {{ st.name }}
                                            {{
                                                unit.institution.id
                                                    ? "yes"
                                                    : "No"
                                            }}
                                        </p>
                                        <div
                                            class="flex justify-start space-x-4"
                                        >
                                            <span
                                                v-if="
                                                    props.unit.type ==
                                                    'Department'
                                                "
                                                class="text-sm"
                                            >
                                                Units:
                                                {{ st.subs }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <div v-for="sub in props.unit.subs" :key="sub.id">
                                <li
                                    v-for="stf in sub.staff"
                                    :key="stf.id"
                                    class="flex items-center text-gray-600 dark:text-gray-200 justify-between py-4 px-4 rounded-xl hover:bg-slate-200"
                                >
                                    <div
                                        class="flex items-center justify-start text-lg"
                                    >
                                        <div class="flex flex-col">
                                            <Link
                                                :href="
                                                    route('institution.staff', {
                                                        institution:
                                                            unit.institution.id,
                                                        staff: stf.id,
                                                    })
                                                "
                                                class=""
                                            >
                                                {{ stf.name }}
                                            </Link>
                                            <div
                                                class="flex justify-start space-x-4"
                                            >
                                                <span
                                                    v-if="
                                                        props.unit.type ==
                                                        'Department'
                                                    "
                                                    class="text-sm"
                                                >
                                                    Units:
                                                    {{ st.subs }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </div>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
