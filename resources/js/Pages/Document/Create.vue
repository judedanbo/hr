<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import UploadForm from "./partials/UploadForm.vue";

const props = defineProps({
    documentTypes: { type: Array, default: () => [] },
    documentStatuses: { type: Array, default: () => [] },
});

const BreadCrumpLinks = [
    { name: "Documents", url: route("document.index") },
    { name: "Upload", url: "" },
];
</script>

<template>
    <MainLayout>
        <Head title="Upload Document" />
        <main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <UploadForm :document-types="documentTypes" :document-statuses="documentStatuses" />
            </div>
        </main>
    </MainLayout>
</template>
