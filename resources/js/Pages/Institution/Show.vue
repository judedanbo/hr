<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { BadgeCheckIcon } from "@heroicons/vue/outline";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

let props = defineProps({
    institution: Object,
    filters: Object,
});

//
let BreadcrumbLinks = [
    { name: "Institutions", url: route("institution.index") },
    { name: props.institution.name },
];
</script>

<template>
    <Head title="Dashboard" />

    <MainLayout>
        <template #header>
            <BreadCrumpVue :links="BreadcrumbLinks" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Institution
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
                                    {{ institution.name }}
                                </h1>
                            </div>
                            <div>Staff</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
