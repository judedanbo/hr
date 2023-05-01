<script setup>
import NewLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { Inertia } from "@inertiajs/inertia";
import { PlusIcon } from "@heroicons/vue/24/outline";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import InfoCard from "@/Components/InfoCard.vue";
import NoItem from "@/Components/NoItem.vue";
import PageHeader from "@/Components/PageHeader.vue";
import UnitCard from '../Unit/UnitCard.vue'
import Modal from "@/Components/Modal.vue";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import { useToggle } from "@vueuse/core";
import InputError from "@/Components/InputError.vue";

let props = defineProps({
    institution: Object,
    departments: Array,
    filters: Object,
    unitTypes: Array,
});



const form = useForm({
    name: null,
    abbreviation: null,
    type: null,
    unit_id: null,
    start_date: format(new Date(), 'yyyy-MM-dd'),
    institution_id: props.institution.id,
});

let open = ref(false);

let toggle = useToggle(open)

const submitForm = () => {
    form.post(route("unit.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            toggle()
        }
    });
};

const newDepartment = () => {
    form.reset();
    toggle()
}

const closeModal = () => {
    form.reset();
    toggle()
}


//
// let BreadcrumbLinks = [
//     { name: "Institutions", url: route("institution.index") },
//     { name: props.institution.name },
// ];

let editDepartment = (id) => {
    // console.log("edit department" + id);
    const dept = props.departments.find((department) => department.id === id);
    form.reset();
    form.name = dept.name;
    form.type = dept.type;
    form.unit_id = dept.unit_id;
    form.start_date = dept.start_date;
    toggle()
}

let search = ref(props.filters.search);

watch(
    search,
    debounce(function (value) {
        Inertia.get(
            route("institution.show", {
                institution: props.institution.id,
            }),
            { search: value },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);
</script>

<template>
    <Head v-if="institution" :title="institution.name" />

    <NewLayout>
        <template #header>
            <PageHeader v-if="institution" :name="institution.name" />

        </template>

        <main>
            <div class="relative isolate overflow-hidden pt-16">
                <!-- Secondary navigation -->
                <header class="pb-4 pt-6 sm:pb-6">
                    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-6 px-4 sm:flex-nowrap sm:px-6 lg:px-8">
                        <h1 class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-100">{{ institution.name
                        }}
                        </h1>
                        <div
                            class="order-last flex w-full gap-x-8 text-sm font-semibold leading-6 sm:order-none sm:w-auto sm:border-l sm:border-gray-200 sm:pl-6 sm:leading-7">

                            <a class="text-gray-700 dark:text-gray-50">Departments</a>
                            <a class="text-gray-700 dark:text-gray-50">Units</a>
                            <a class="text-gray-700 dark:text-gray-50">Staff</a>
                            <a class="text-gray-700 dark:text-gray-50">Heads</a>
                        </div>
                        <a @click.prevent="newDepartment" href="#"
                            class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                            <PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
                            New Department
                        </a>
                    </div>
                </header>

                <!-- Stats -->
                <div class="border-b border-b-gray-900/10 lg:border-t lg:border-t-gray-900/5">
                    <dl class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0">
                        <div
                            class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
                            <dt class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50">Departments</dt>
                            <dd class="text-gray-700', 'text-xs font-medium">
                                0.5%</dd>
                            <dd
                                class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50">
                                {{
                                    institution.departments }}</dd>
                        </div>
                        <div
                            class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
                            <dt class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50">Divisions</dt>
                            <dd class="text-gray-700', 'text-xs font-medium">
                                0.5%</dd>
                            <dd
                                class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50">
                                {{
                                    institution.divisions }}</dd>
                        </div>
                        <div
                            class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
                            <dt class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50">Units</dt>
                            <dd class="text-gray-700', 'text-xs font-medium">
                                0.5%</dd>
                            <dd
                                class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50">
                                {{
                                    institution.units }}</dd>
                        </div>
                        <div
                            class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
                            <dt class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50">Staff</dt>
                            <dd class="text-gray-700', 'text-xs font-medium">
                                0.5%</dd>
                            <dd
                                class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50">
                                {{
                                    institution.staff }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="absolute left-0 top-full -z-10 mt-96 origin-top-left translate-y-40 -rotate-90 transform-gpu opacity-20 blur-3xl sm:left-1/2 sm:-ml-96 sm:-mt-10 sm:translate-y-0 sm:rotate-0 sm:transform-gpu sm:opacity-50"
                    aria-hidden="true">
                    <div class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-[#0adb3b] to-[#9089FC] dark:from-white dark:to-white"
                        style="clip-path: polygon(100% 38.5%, 82.6% 100%, 60.2% 37.7%, 52.4% 32.1%, 47.5% 41.8%, 45.2% 65.6%, 27.5% 23.4%, 0.1% 35.3%, 17.9% 0%, 27.7% 23.4%, 76.2% 2.5%, 74.2% 56%, 100% 38.5%)" />
                </div>
            </div>

            <div class="space-y-16 py-16 xl:space-y-20">

                <!-- department list-->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold leading-7 text-gray-900">Departments</h2>

                        </div>
                        <ul role="list" class="mt-6 grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
                            <template v-for="department in departments" :key="department.id">

                                <UnitCard @editItem="(id) => editDepartment(id)" :unit="department" />
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
            <Modal @close-modal="closeModal" :open="open" title="Create Department">

                <form @submit.prevent="submitForm" action="#">
                    <div class="grid gap-4 mb-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name of
                                department</label>
                            <input v-model="form.name" type="text" id="name"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
                                placeholder="Name of department">
                            <InputError :message="form.errors.name" />
                        </div>
                        <div>
                            <label for="institution" id="institution"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Institution</label>
                            <input v-model="institution.name" disabled
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500 disabled:bg-gray-300"
                                placeholder="institution">
                            <InputError :message="form.errors.institution_id" />
                        </div>
                        <div>
                            <label for="parent" class="block text-sm font-medium leading-6 text-gray-900">Parent</label>
                            <select v-model="form.unit_id" id="parent" name="parent"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-green-600 sm:text-sm sm:leading-6">
                                <option>None</option>
                                <option v-for="department in props.departments" :key="department.id" :value="department.id">
                                    {{
                                        department.name }}</option>
                            </select>
                            <InputError :message="form.errors.unit_id" />
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium leading-6 text-gray-900">Type</label>
                            <select v-model="form.type" id="parent" name="parent"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-green-600 sm:text-sm sm:leading-6">
                                <option value="">Select One</option>
                                <option value="DEP">Department</option>
                                <option value="DIV">Division</option>
                                <option value="SEC">Section</option>
                                <option value="BRH">Branch</option>
                                <option value="MU">Management Unit</option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div>
                            <label for="parent" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Start date
                            </label>
                            <input v-model="form.start_date" type="date" :max="format(new Date(), 'yyyy-MM-dd')"
                                min="2000-01-01"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500">
                            <InputError :message="form.errors.start_date" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between space-x-4">
                        <button type="submit" :disabled="form.processing"
                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 disabled:opacity-50">
                            Add department
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
        </main>
    </NewLayout>
</template>
