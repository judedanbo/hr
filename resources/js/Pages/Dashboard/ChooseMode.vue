<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";

defineProps({
    staffOption: { type: Object, required: true },
    otherOption: { type: Object, required: true },
});

function chooseMode(mode) {
    router.post(route("dashboard.switch-mode"), { mode });
}
</script>

<template>
    <MainLayout>
        <Head title="Choose your view" />
        <main class="mx-auto max-w-3xl px-4 py-12">
            <h1
                class="text-2xl font-semibold text-gray-900 dark:text-gray-50"
            >
                How would you like to continue?
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                You have access to multiple views. Pick one to land on —
                you can switch any time using the header.
            </p>
            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white p-6 text-left shadow-sm transition hover:border-indigo-500 hover:shadow dark:border-gray-700 dark:bg-gray-800"
                    @click="chooseMode(staffOption.mode)"
                >
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-gray-50"
                    >
                        {{ staffOption.label }}
                    </h2>
                    <p
                        class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                    >
                        {{ staffOption.description }}
                    </p>
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white p-6 text-left shadow-sm transition hover:border-indigo-500 hover:shadow dark:border-gray-700 dark:bg-gray-800"
                    @click="chooseMode(otherOption.mode)"
                >
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-gray-50"
                    >
                        {{ otherOption.label }}
                    </h2>
                    <p
                        class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                    >
                        {{ otherOption.description }}
                    </p>
                </button>
            </div>
        </main>
    </MainLayout>
</template>
