<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";

import BreadCrumpVue from "@/Components/BreadCrump.vue";

let props = defineProps({
    jobs: Object,
    filters: Object,
});

let search = ref(props.filters.search);

watch(
    search,
    debounce(function (value) {
        Inertia.get(
            route("job.index"),
            { search: value },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);

let BreadCrumpLinks = [
    {
        name: "Jobs",
    },
];
</script>

<template>
    <Head title="Jobs" />

    <MainLayout>
        <template #header>
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Jobs
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div
                            class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2 lg:grid-cols-4"
                        ></div>
                        <div class="sm:flex justify-between my-6">
                            <h3 class="mb-4 text-xl">Jobs</h3>

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
                                    class="w-full pl-8 bg-slate-100 border-0"
                                    required
                                    autofocus
                                    placeholder="Search Jobs..."
                                />
                            </div>
                        </div>
                        <div class="flex flex-col mt-6">
                            <div
                                class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"
                            >
                                <div
                                    class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
                                >
                                    <div
                                        class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
                                    >
                                        <table
                                            v-if="jobs.total > 0"
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
                                                        Staff
                                                    </th>

                                                    <th
                                                        role="col"
                                                        class="relative px-6 py-3"
                                                    >
                                                        <span class="sr-only"
                                                            >Edit</span
                                                        >
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="bg-white divide-y divide-gray-200"
                                            >
                                                <tr
                                                    v-for="job in jobs.data"
                                                    :key="job.id"
                                                    class="transition-all hover:bg-gray-100 hover:shadow-lg"
                                                >
                                                    <td
                                                        class="px-6 py-2 whitespace-nowrap"
                                                    >
                                                        <div
                                                            class="flex items-center"
                                                        >
                                                            <div
                                                                class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-full flex justify-center items-center"
                                                            ></div>

                                                            <div class="ml-4">
                                                                <div
                                                                    class="text-sm font-medium text-gray-900"
                                                                >
                                                                    {{
                                                                        job.name
                                                                    }}
                                                                    {{
                                                                        job.abbreviation
                                                                            ? "(" +
                                                                              job.abbreviation +
                                                                              ")"
                                                                            : ""
                                                                    }}
                                                                </div>
                                                                <div
                                                                    class="text-sm text-gray-500"
                                                                ></div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap"
                                                    >
                                                        <div
                                                            class="text-sm text-gray-900 text-center"
                                                        >
                                                            {{
                                                                job.staff.toLocaleString()
                                                            }}
                                                        </div>
                                                    </td>

                                                    <td
                                                        class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
                                                    >
                                                        <Link
                                                            :href="
                                                                route(
                                                                    'job.show',
                                                                    {
                                                                        job: job.id,
                                                                    }
                                                                )
                                                            "
                                                            class="text-green-600 hover:text-green-900"
                                                            >Show</Link
                                                        >
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <Pagination :records="jobs" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
