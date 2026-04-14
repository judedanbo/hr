<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

const props = defineProps({
    note: { type: Object, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const getNoteTypeBadgeClass = (noteType) => {
    if (!noteType) return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";

    if (["RET", "DEC", "RES", "DIS", "TER"].includes(noteType)) {
        return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
    }
    if (["LWP", "SIC", "ANN", "MAT", "STU", "SAB"].includes(noteType)) {
        return "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300";
    }
    if (["INT", "SUS"].includes(noteType)) {
        return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300";
    }
    return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
};

const BreadCrumpLinks = [
    { name: "Notes", url: route("notes.index") },
    { name: `Note #${props.note.id}`, url: "" },
];
</script>

<template>
    <MainLayout>
        <Head :title="`Note #${note.id}`" />
        <main
            v-if="permissions?.includes('view staff notes')"
            class="max-w-4xl mx-auto sm:px-6 lg:px-8"
        >
            <BreadCrumpVue :links="BreadCrumpLinks" />

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Header -->
                <div
                    class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                >
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            Note Details
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ note.created_at }}
                        </p>
                    </div>
                    <span
                        v-if="note.note_type_label"
                        :class="[
                            'px-3 py-1 text-sm font-medium rounded-full',
                            getNoteTypeBadgeClass(note.note_type),
                        ]"
                    >
                        {{ note.note_type_label }}
                    </span>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 space-y-6">
                    <!-- Note Content -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Note
                        </h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                            {{ note.note }}
                        </p>
                    </div>

                    <!-- Note Date -->
                    <div v-if="note.note_date">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Date
                        </h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ note.note_date }}
                        </p>
                    </div>

                    <!-- Associated Staff -->
                    <div v-if="note.notable_name">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Associated With
                        </h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ note.notable_name }}
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                ({{ note.notable_type }} #{{ note.notable_id }})
                            </span>
                        </p>
                    </div>

                    <!-- URL -->
                    <div v-if="note.url">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Reference URL
                        </h3>
                        <a
                            :href="note.url"
                            target="_blank"
                            class="mt-1 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 break-all"
                        >
                            {{ note.url }}
                        </a>
                    </div>

                    <!-- Documents -->
                    <div v-if="note.documents && note.documents.length > 0">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                            Attached Documents
                        </h3>
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li
                                v-for="doc in note.documents"
                                :key="doc.id"
                                class="py-2 flex items-center justify-between"
                            >
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ doc.document_title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ doc.file_name }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded"
                                >
                                    {{ doc.document_type }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Timestamps -->
                    <div
                        class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700"
                    >
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Created At
                            </h3>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ note.created_at }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Updated At
                            </h3>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ note.updated_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"
                >
                    <Link
                        :href="route('notes.index')"
                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        &larr; Back to Notes
                    </Link>
                </div>
            </div>
        </main>
        <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600 dark:text-gray-400">
                    You do not have permission to view notes.
                </p>
            </div>
        </div>
    </MainLayout>
</template>
