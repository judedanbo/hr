<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { onMounted, ref, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import RecruitmentChart from "./Chart.vue";
import PageHeader from "@/Components/PageHeader.vue";

let props = defineProps({
    recruitment: Object,
});

let download = () => {
    const imageLink = document.createElement("a");
    const canvas = document.getElementById("bar-chart");

    imageLink.href = canvas.toDataURL("image/jpeg", 1);
    imageLink.download = "Employment History.jpeg";
    // document.write('<img src="' + imageLink + '"/>');
    imageLink.click();
};

let selectedYears = ref(new Set());

let getRetired = () => {
    Inertia.get(
        route("report.recruitment.chart"),
        { retired: true },
        { preserveState: true, replace: true, preserveScroll: true }
    );
};

let getActive = () => {
    Inertia.get(
        route("report.recruitment.chart"),
        { active: true },
        { preserveState: true, replace: true, preserveScroll: true }
    );
};
let getAll = () => {
    Inertia.get(
        route("report.recruitment.chart"),
        {},
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        }
    );
};

let showDetails = (year) => {
    Inertia.get(
        route("report.recruitment.details", { year: year }),
        {},
        { preserveState: true, replace: true }
    );
};

let addYear = (year) => {
    if (selectedYears.value.has(year)) {
        selectedYears.value.delete(year);
    } else {
        selectedYears.value.add(year);
    }
};
let addAllYears = () => {
    if (selectedYears.value.size > recruitment) {
        selectedYears.value.delete(year);
    } else {
        selectedYears.value.add(year);
    }
    console.log(selectedYears.value);
};

let BreadCrumpLinks = [
    {
        name: "Reports",
        url: route("report.index"),
    },
    {
        name: "Recruitment",
        url: route("report.recruitment"),
    },
    {
        name: "charts",
        url: "",
    },
];
</script>

<template>
    <Head title="Recruitment" />

    <MainLayout>
        <template #header>
            <PageHeader name='Recruitment' />
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 border-b border-gray-200">
                    <div class="flex flex-col mt-6">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <div class="flex gap-4 flex-wrap justify-center">
                                    <div class="sm:px-6 w-full">
                                        <div class="px-4 md:px-10">
                                            <div class="flex items-center justify-between">
                                                <p tabindex="0"
                                                    class="focus:outline-none text-base sm:text-lg md:text-xl lg:text-2xl font-bold leading-normal text-gray-800">
                                                    Year and Number Recruited
                                                </p>
                                                <div
                                                    class="py-3 px-4 flex items-center text-sm font-medium leading-none text-gray-600 bg-gray-200 hover:bg-gray-300 cursor-pointer rounded">
                                                    <p>Sort By:</p>
                                                    <select aria-label="select"
                                                        class="focus:text-green-600 focus:outline-none bg-transparent ml-1">
                                                        <option class="text-sm text-green-800">
                                                            Latest
                                                        </option>
                                                        <option class="text-sm text-green-800">
                                                            Oldest
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex gap-4">
                                            <div class="bg-white py-4 md:py-7 px-4 md:px-8 xl:px-10 w-full">
                                                <div class="sm:flex items-center justify-between justify-items-start">
                                                    <div class="flex items-center">
                                                        <div @click="getAll"
                                                            class="rounded-full focus:outline-none focus:ring-2 focus:bg-green-50 focus:ring-green-800 cursor-pointer">
                                                            <div :class="{
                                                                    ' bg-green-100 text-green-700 font-bold':
                                                                        route().current(
                                                                            'report.recruitment.chart',
                                                                            {
                                                                                retired:
                                                                                    null,
                                                                                active: null,
                                                                            }
                                                                        ),
                                                                }" class="py-2 px-8 rounded-full">
                                                                <p>All</p>
                                                            </div>
                                                        </div>
                                                        <div @click="getActive"
                                                            class="rounded-full focus:outline-none focus:ring-2 focus:bg-green-50 focus:ring-green-800 ml-4 sm:ml-8 cursor-pointer">
                                                            <div :class="{
                                                                    'bg-green-100 text-green-700 font-bold':
                                                                        route().current(
                                                                            'report.recruitment.chart',
                                                                            {
                                                                                active: 'true',
                                                                            }
                                                                        ),
                                                                }"
                                                                class="py-2 px-8 text-gray-600 hover:text-green-700 hover:bg-green-100 rounded-full">
                                                                <p>Active</p>
                                                            </div>
                                                        </div>
                                                        <div @click="getRetired" :class="{
                                                                'bg-green-100 text-green-700 font-bold':
                                                                    route().current(
                                                                        'report.recruitment.chart',
                                                                        {
                                                                            retired:
                                                                                'true',
                                                                        }
                                                                    ),
                                                            }"
                                                            class="rounded-full focus:outline-none focus:ring-2 focus:bg-green-50 focus:ring-green-800 ml-4 sm:ml-8 cursor-pointer">
                                                            <div
                                                                class="py-2 px-8 text-gray-600 hover:text-green-700 hover:bg-green-100 rounded-full">
                                                                <p>Retired</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-7 overflow-x-auto">
                                                    <RecruitmentChart :recruitment="recruitment
                                                        " title="Last Ten Recruitment" />
                                                    <div class="flex space-x-3 justify-center mt-4">
                                                        <div @click="download"
                                                            class="cursor-pointer px-4 py-1 rounded-full border-2 border-green-500 hover:text-white hover:bg-green-800 hover:border-white">
                                                            Download
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
