<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import format from "date-fns/format";
import differenceInYears from "date-fns/differenceInYears";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import ReportCard from "@/Components/ReportCard.vue";
import RecruitmentChart from "./Recruitment/Chart.vue";

let BreadCrumpLinks = [
    {
        name: "Reports",
        url: "",
    },
];
</script>

<template>
    <Head title="Reports" />

    <MainLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Reports
            </h2>
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 border-b border-gray-200">
                    <div class="flex flex-col mt-6">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div
                                class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
                            >
                                <div
                                    class="flex gap-4 flex-wrap justify-center"
                                >
                                    <ReportCard
                                        :href="route('report.recruitment')"
                                        text="By Recruitment"
                                    />
                                    <ReportCard href="#" text="By Department" />
                                    <ReportCard href="#" text="By Unit" />
                                    <ReportCard href="#" text="By Ranks" />
                                    <ReportCard href="#" text="By Location" />
                                </div>
                                <!-- <RecruitmentChart
                                    :data="[50, 52]"
                                    :label="['jan', 'dec']"
                                /> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
