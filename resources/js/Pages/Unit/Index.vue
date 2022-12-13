<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import InfoCard from "@/Components/InfoCard.vue";
import NoItem from "@/Components/NoItem.vue";

import BreadCrumpVue from "@/Components/BreadCrump.vue";

let props = defineProps({
    units: Object,
    filters: Object,
});

let search = ref(props.filters.search);

watch(
    search,
    debounce(function (value) {
        Inertia.get(
            route("unit.index"),
            { search: value },
            { preserveState: true, replace: true }
        );
    }, 300)
);

let openUnit = (unit) => {
    Inertia.visit(route("unit.show", { unit: unit }));
};

let BreadCrumpLinks = [
    {
        name: props.units.data[0].institution.name,
        url: route("institution.show", {
            institution: props.units.data[0].institution.id,
        }),
    },
    {
        name: "Departments",
    },
];
</script>

<template>
    <Head title="Departments" />

    <MainLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Departments
            </h2>
        </template>
        <div class="overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <BreadCrumpVue :links="BreadCrumpLinks" />
                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4"
                ></div>
                <div class="sm:flex items-center justify-between my-2">
                    <InfoCard title="Units" :value="units.total" />
                    <div class="mt-1 relative mx-8">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <span class="text-gray-500 sm:text-sm">
                                <MagnifyingGlassIcon class="w-4 h-4" />
                            </span>
                        </div>
                        <BreezeInput
                            v-model="search"
                            type="search"
                            class="w-full pl-8 bg-white border-0"
                            required
                            autofocus
                            placeholder="Search units..."
                        />
                    </div>
                </div>
                <div v-if="units.total > 0" class="flex flex-col mt-2">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div
                            class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
                        >
                            <div
                                class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
                            >
                                <table
                                    class="min-w-full overflow-x-scroll divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                            >
                                                Name
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                            >
                                                Institution
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            @click="openUnit(unit.id)"
                                            v-for="unit in units.data"
                                            :key="unit.id"
                                            class="cursor-pointer transition-all hover:bg-gray-100 hover:shadow-lg"
                                        >
                                            <td
                                                class="px-6 py-2 whitespace-nowrap"
                                            >
                                                <div class="flex items-center">
                                                    <div
                                                        class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-full flex justify-center items-center"
                                                    ></div>

                                                    <div class="ml-4">
                                                        <div
                                                            class="text-sm font-medium text-gray-900"
                                                        >
                                                            {{ unit.name }}
                                                        </div>
                                                        <div
                                                            class="text-sm text-gray-500"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td
                                                class="px-6 py-4 text-sm font-medium whitespace-nowrap"
                                            >
                                                <Link
                                                    :href="
                                                        route(
                                                            'institution.show',
                                                            {
                                                                institution:
                                                                    unit
                                                                        .institution
                                                                        .id,
                                                            }
                                                        )
                                                    "
                                                    v-text="
                                                        unit.institution.name
                                                    "
                                                >
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <Pagination :records="units" />
                            </div>
                        </div>
                    </div>
                </div>
                <NoItem v-else name="Department" />
            </div>
        </div>
    </MainLayout>
</template>
