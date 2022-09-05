<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

const formattedDob = (dateString) => {
    const date = new Date(dateString);
    return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString) => {
    const date = new Date(dateString);
    return differenceInYears(new Date(), date);
};

let props = defineProps({
    person: Object,
    filters: Object,
});

let tabs = [
    { name: "Profile", active: true },
    { name: "Account" },
    { name: "Notification" },
];
let BreadcrumbLinks = [
    { name: "People", url: "/person" },
    { name: props.person.initials, url: "/" },
];
</script>

<template>
    <Head title="Dashboard" />

    <MainLayout>
        <template #header>
            <BreadCrumpVue :links="BreadcrumbLinks" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Person
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div
                        class="p-10 bg-white border-b border-gray-200 md:flex justify-around"
                    >
                        <div class="flex flex-col md:flex-row items-center">
                            <div
                                class="w-48 h-48 rounded-full bg-gray-400 flex justify-center items-center"
                            >
                                <h1
                                    class="text-white font-semibold text-6xl tracking-widest"
                                >
                                    {{ person.initials }}
                                </h1>
                            </div>
                            <div class="p-8 ml-6">
                                <h1
                                    class="text-3xl lg:text-4xl font-bold tracking-wider text-gray-700"
                                >
                                    {{ person.name }}
                                </h1>
                                <p class="text-lg tracking-wide py-2">
                                    {{ formattedDob(person.dob) }} ({{
                                        getAge(person.dob)
                                    }}
                                    years)
                                </p>
                                <p class="text-lg tracking-wide">
                                    SSNIT No. {{ person.ssn }}
                                </p>
                            </div>
                            <div>Contacts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
