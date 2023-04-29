<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { MagnifyingGlassIcon, PlusIcon } from "@heroicons/vue/24/outline";
import PageHeader from '@/Components/PageHeader.vue'
import { useToggle } from "@vueuse/core";
import { format } from "date-fns";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

let props = defineProps({
    institutions: Object,
    filters: Object,
});

const form = useForm({
    name: null,
    abbreviation: null,
    start_date: format(new Date(), 'yyyy-MM-dd'),
    institution_id: null,
});

let open = ref(false);

let toggle = useToggle(open)

const submitForm = () => {
    form.post(route("institution.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            toggle()
        }
    });
};

let search = ref(props.filters.search);

watch(
    search,
    debounce(function (value) {
        Inertia.get(
            route("institution.index"),
            { search: value },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);

let BreadCrumpLinks = [
    {
        name: "Institutions",
    },
];
</script>

<template>
    <Head title="Institutions" />

    <MainLayout>
        <template #header>
            <PageHeader name="Institutions" />
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-4 bg-white border-b border-gray-200">
                    <BreadCrumpVue :links="BreadCrumpLinks" />
                    <div class="flex justify-end">
                        <div class="mt-1 relative mx-8">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">
                                    <MagnifyingGlassIcon class="w-4 h-4" />
                                </span>
                            </div>
                            <BreezeInput v-model="search" type="search" class="w-full pl-8 bg-slate-100 border-0" required
                                autofocus placeholder="Search institutions..." />
                        </div>
                        <a @click.prevent="toggle()" href="#"
                            class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                            <PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
                            New Institutions
                        </a>
                    </div>
                    <div class="flex flex-col mt-2">
                        <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <div class="overflow-hidden border-b border-gray-200 rounded-md shadow-md">
                                    <table v-if="institutions.total > 0"
                                        class="min-w-full overflow-x-scroll divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                                    Name
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                                    Departments
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                                    Divisions
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                                    Units
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                                    Staff
                                                </th>

                                                <th role="col" class="relative px-6 py-3">
                                                    <span class="sr-only">Edit</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="institution in institutions.data" :key="institution.id"
                                                class="transition-all hover:bg-gray-100 hover:shadow-lg">
                                                <td class="px-6 py-2 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div
                                                            class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-full flex justify-center items-center">
                                                        </div>

                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{
                                                                    institution.name
                                                                }}
                                                                {{
                                                                    institution.abbreviation
                                                                    ? "(" +
                                                                    institution.abbreviation +
                                                                    ")"
                                                                    : ""
                                                                }}
                                                            </div>
                                                            <div class="text-sm text-gray-500"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900 text-center">
                                                        {{
                                                            institution.departments
                                                        }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900 text-center">
                                                        {{ institution.units }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900 text-center">
                                                        {{
                                                            institution.divisions.toLocaleString()
                                                        }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900 text-center">
                                                        {{
                                                            institution.staff.toLocaleString()
                                                        }}
                                                    </div>
                                                </td>

                                                <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                                    <Link :href="route(
                                                                'institution.show',
                                                                {
                                                                    institution:
                                                                        institution.id,
                                                                }
                                                            )
                                                            " class="text-green-600 hover:text-green-900">Show</Link>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <Pagination :records="institutions" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Modal @close-modal="toggle" :open="open" title="Create Institution">
                <form @submit.prevent="submitForm" action="#">
                    <div class="grid gap-4 mb-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name of
                                institution</label>
                            <input v-model="form.name"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
                                placeholder="Name of institution">
                            <InputError :message="form.errors.name" />
                        </div>
                        <div>
                            <label for="brand"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Abbreviation</label>
                            <input v-model="form.abbreviation"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
                                placeholder="Abbreviation">
                            <InputError :message="form.errors.abbreviation" />
                        </div>
                        <div>
                            <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Start date
                            </label>
                            <input v-model="form.start_date" type="date" :max="format(new Date(), 'yyyy-MM-dd')"
                                min="2000-01-01"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500">
                            <InputError :message="form.errors.start_date" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between space-x-4">
                        <button type="submit"
                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                            Add institution
                        </button>
                        <button @click.prevent="form.reset()" type="button"
                            class="text-red-600 inline-flex items-center hover:text-white border border-red-600 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-900">
                            <svg class="mr-1 -ml-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Reset
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    </MainLayout>
</template>
