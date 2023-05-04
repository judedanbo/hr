<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch, computed } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import InfoCard from "@/Components/InfoCard.vue";
import NoItem from "@/Components/NoItem.vue";

import BreadCrumpVue from "@/Components/BreadCrump.vue";

let props = defineProps({
    promotions: Array,
    filters: Object,
});

const promos = computed(() => {
    $list = {}
    props.promotions.map((promo) => {
        $list[promo.effective_date] = promo
    })
    // return props.promotions.;
});

const $dateFormat = (date) => {
    return new Date(date)
}
// let search = ref(props.filters.search);

// watch(
//     search,
//     debounce(function (value) {
//         Inertia.get(
//             route("unit.index"),
//             { search: value },
//             { preserveState: true, replace: true }
//         );
//     }, 300)
// );

// let openUnit = (unit) => {
//     Inertia.visit(route("unit.show", { unit: unit }));
// };

// let BreadCrumpLinks = [
//     {
//         name: props.units.data[0].institution.name,
//         url: route("institution.show", {
//             institution: props.units.data[0].institution.id,
//         }),
//     },
//     {
//         name: "Departments",
//     },
// ];

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
                <h2 class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 lg:mx-0 lg:max-w-none">
                    Promotion History</h2>
            </div>
            <div class="mt-6 overflow-hidden border-t border-gray-100">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
                        <table class="w-full text-left">
                            <thead class="sr-only">
                                <tr>
                                    <th>Position</th>
                                    <th class="hidden sm:table-cell">Staff</th>
                                    <th>More details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(promotion, index) in promotions" :key="index">
                                    <tr class="text-sm leading-6 text-gray-900">
                                        <th scope="colgroup" colspan="3" class="relative isolate py-2 font-semibold">
                                            <time :datetime="promotion.index">{{ new
                                                Date(promotion.effective_date).toLocaleDateString('en-US', { month: long })
                                            }}</time>
                                            <div
                                                class="absolute inset-y-0 right-full -z-10 w-screen border-b border-gray-200 bg-gray-50" />
                                            <div
                                                class="absolute inset-y-0 left-0 -z-10 w-screen border-b border-gray-200 bg-gray-50" />
                                        </th>
                                    </tr>

                                    <tr v-for="(promo, index) in promotion.promos" :key="index">
                                        <td class="relative py-5 pr-6">
                                            <div class="flex gap-x-6">
                                                <!-- <component :is="transaction.icon"
                                                    class="hidden h-6 w-5 flex-none text-gray-400 sm:block"
                                                    aria-hidden="true" /> -->
                                                <div class="flex-auto">
                                                    <div class="flex items-start gap-x-3">
                                                        <div class="text-sm font-medium leading-6 text-gray-900">{{
                                                            promo.job_name }}</div>

                                                    </div>
                                                    <div v-if="promo.tax" class="mt-1 text-xs leading-5 text-gray-500">{{
                                                        promo.tax }}
                                                        tax</div>
                                                </div>
                                            </div>
                                            <div class="absolute bottom-0 right-full h-px w-screen bg-gray-100" />
                                            <div class="absolute bottom-0 left-0 h-px w-screen bg-gray-100" />
                                        </td>
                                        <td class="hidden py-5 pr-6 sm:table-cell">
                                            <div class="text-sm leading-6 text-gray-900">{{ promo.staff }}</div>

                                        </td>
                                        <td class="py-5 text-right">
                                            <div class="flex justify-end">
                                                <a :href="promo.href"
                                                    class="text-sm font-medium leading-6 text-indigo-600 hover:text-indigo-500">View<span
                                                        class="hidden sm:inline"> Details</span><span class="sr-only">,
                                                        invoice #{{ promo.invoiceNumber }}, {{ promo.client
                                                        }}</span></a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
