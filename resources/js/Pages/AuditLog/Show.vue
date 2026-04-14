<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

const props = defineProps({
    activity: { type: Object, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const getEventBadgeClass = (event) => {
    switch (event) {
        case "created":
            return "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300";
        case "updated":
            return "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300";
        case "deleted":
            return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
        case "authorization_failed":
            return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300";
        case "authorization_success":
        case "success":
            return "bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300";
        default:
            return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
    }
};

const formatProperties = (properties) => {
    if (!properties || Object.keys(properties).length === 0) {
        return null;
    }
    return JSON.stringify(properties, null, 2);
};

const BreadCrumpLinks = [
    { name: "Audit Log", url: route("audit-log.index") },
    { name: `Activity #${props.activity.id}`, url: "" },
];
</script>

<template>
    <MainLayout>
        <Head :title="`Activity #${activity.id}`" />
        <main
            v-if="permissions?.includes('view user activity')"
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
                            Activity Details
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ activity.created_at }}
                        </p>
                    </div>
                    <span
                        :class="[
                            'px-3 py-1 text-sm font-medium rounded-full',
                            getEventBadgeClass(activity.event),
                        ]"
                    >
                        {{ activity.event || "N/A" }}
                    </span>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 space-y-6">
                    <!-- Description -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Description
                        </h3>
                        <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                            {{ activity.description }}
                        </p>
                    </div>

                    <!-- Log Name -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Log Name
                        </h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ activity.log_name || "default" }}
                        </p>
                    </div>

                    <!-- Causer -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Performed By
                        </h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ activity.causer_name }}
                            <span
                                v-if="activity.causer_type"
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                ({{ activity.causer_type }} #{{ activity.causer_id }})
                            </span>
                        </p>
                    </div>

                    <!-- Subject -->
                    <div v-if="activity.subject_type">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Subject
                        </h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ activity.subject_type }} #{{ activity.subject_id }}
                            <span
                                v-if="activity.subject_name"
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                ({{ activity.subject_name }})
                            </span>
                        </p>
                    </div>

                    <!-- Properties -->
                    <div v-if="formatProperties(activity.properties)">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                            Properties
                        </h3>
                        <pre
                            class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"
                        >{{ formatProperties(activity.properties) }}</pre>
                    </div>

                    <!-- Batch UUID -->
                    <div v-if="activity.batch_uuid">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Batch UUID
                        </h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100 font-mono text-sm">
                            {{ activity.batch_uuid }}
                        </p>
                    </div>

                    <!-- Timestamps -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Created At
                            </h3>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ activity.created_at }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Updated At
                            </h3>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ activity.updated_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700"
                >
                    <Link
                        :href="route('audit-log.index')"
                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        &larr; Back to Audit Log
                    </Link>
                </div>
            </div>
        </main>
        <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600 dark:text-gray-400">
                    You do not have permission to view audit logs.
                </p>
            </div>
        </div>
    </MainLayout>
</template>
