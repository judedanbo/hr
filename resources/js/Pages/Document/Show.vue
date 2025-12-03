<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

const props = defineProps({
    document: { type: Object, required: true },
});

const BreadCrumpLinks = [
    { name: "Documents", url: route("document.index") },
    { name: props.document.document_title, url: "" },
];
</script>

<template>
    <MainLayout>
        <Head :title="document.document_title" />
        <main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ document.document_title }}</h1>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ document.document_number }}</p>
                        </div>
                        <div class="flex gap-2">
                            <a
                                :href="route('document.download', { document: document.id })"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Download
                            </a>
                            <Link
                                :href="route('document.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Back to List
                            </Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Document Type</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ document.document_type_label }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        {{ document.document_status_label }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">File Name</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ document.file_name || "N/A" }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">File Type</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ document.file_type || "N/A" }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-if="document.documentable_type">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Associated With</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ document.documentable_type }} #{{ document.documentable_id }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ document.created_at }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ document.updated_at }}</p>
                            </div>

                            <div v-if="document.document_remarks">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Remarks</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ document.document_remarks }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </MainLayout>
</template>
