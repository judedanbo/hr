<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import Pagination from "@/Components/Pagination.vue";
import { watch, ref } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { Inertia } from "@inertiajs/inertia";

let props = defineProps({
    promotions: Object,
    filters: Object,
});

let search = ref(props.filters.search);

watch(search, (value) => {
    Inertia.get(route('promotion.index'), { search: value }, { preserveState: true, replace: true, preserveScroll: true })
})

</script>

<template>
    <Head title="Promotions" />

    <MainLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50">
                Promotions
            </h2>
        </template>

        <div>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2
                    class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 dark:text-gray-50 lg:mx-0 lg:max-w-none">
                    Promotion History</h2>
                <input v-model="search" type="search" />
            </div>
            <div class="mt-6 overflow-hidden border-t border-gray-100 dark:border-gray-800">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
                        <table class="w-full text-left">
                            {{ $field = '' }}
                            <thead class="sr-only">
                                <tr>
                                    <th>Position</th>
                                    <th class="hidden sm:table-cell">Staff</th>
                                    <th>More details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(promotion, index) in  promotions.data " :key="index">

                                    <tr v-if="promotion.year != $field" class="text-sm leading-6 text-gray-900 bg-gray-50 dark:bg=gray-800
                                        dark:text-gray-50">
                                        <th scope="col" class="relative isolate py-2 font-semibold">

                                            <p>{{ promotion.year }}</p>
                                            <div
                                                class="absolute inset-y-0 right-full -z-10 w-screen border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800" />
                                            <div
                                                class="absolute inset-y-0 left-0 -z-10 w-screen border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800" />
                                        </th>
                                        <th scope="col" class="relative isolate py-2 font-semibold cursor-pointer">
                                            <Link :href="route('promotion.show', {
                                                year: promotion.year,
                                                month: 'april'

                                            })">April</Link>
                                        </th>
                                        <th scope="col" class="relative isolate py-2 font-semibold cursor-pointer">
                                            <Link :href="route('promotion.show', {
                                                year: promotion.year,
                                                month: 'october',

                                            })">October</Link>
                                        </th>
                                        {{ $field = promotion.year }}
                                    </tr>

                                    <tr>
                                        <td class="relative py-5 pr-6">
                                            <div class="flex gap-x-6">
                                                <!-- <component :is="transaction.icon"
                                                    class="hidden h-6 w-5 flex-none text-gray-400 sm:block"
                                                    aria-hidden="true" /> -->
                                                <div class="flex-auto">
                                                    <div class="flex items-start gap-x-3">
                                                        <div
                                                            class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-50">
                                                            {{
                                                                promotion.job_name }}</div>

                                                    </div>
                                                    <div v-if="promotion.tax" class="mt-1 text-xs leading-5 text-gray-500">
                                                        {{
                                                            promotion.tax }}
                                                        tax</div>
                                                </div>
                                            </div>
                                            <div
                                                class="absolute bottom-0 right-full h-px w-screen bg-gray-100 dark:bg-gray-700" />
                                            <div
                                                class="absolute bottom-0 left-0 h-px w-screen bg-gray-100 dark:bg-gray-700" />
                                        </td>
                                        <td class="hidden py-5 pr-6 sm:table-cell">
                                            <div class="text-sm leading-6 text-gray-900 dark:text-gray-50">{{
                                                promotion.april }}
                                            </div>

                                        </td>
                                        <td class="hidden py-5 pr-6 sm:table-cell">
                                            <div class="text-sm leading-6 text-gray-900 dark:text-gray-50">{{
                                                promotion.october }}
                                            </div>

                                        </td>
                                        <td class="py-5 text-right">
                                            <div class="flex justify-end">
                                                <a :href="promotion.href"
                                                    class="text-sm font-medium leading-6 text-green-600 hover:text-green-500 dark:text-gray-50 dark:hover:text-gray-200">View<span
                                                        class="hidden sm:inline"> Details</span><span class="sr-only">,
                                                        invoice #{{ promotion.invoiceNumber }}, {{ promotion.client
                                                        }}</span></a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <Pagination :records="promotions" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
