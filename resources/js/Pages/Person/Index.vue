<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import format from "date-fns/format";
import differenceInYears from "date-fns/differenceInYears";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

let props = defineProps({
    people: Object,
    filters: Object,
});

let search = ref(props.filters.search);

watch(
    search,
    debounce(function (value) {
        Inertia.get(
            route("person.index"),
            { search: value },
            { preserveState: true, replace: true }
        );
    }, 300)
);

let formatDate = (dateString) => {
    const date = new Date(dateString);
    return format(date, "EEEE dd MMMM, yyyy");
    // return new Intl.DateTimeFormat("en-GB", { dateStyle: "full" }).format(date);
};

let getAge = (dateString) => {
    const date = new Date(dateString);
    // console.log(Date);

    return differenceInYears(new Date(), date);
};
let BreadCrumpLinks = [
    {
        name: "Person",
        url: "",
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <MainLayout>
        <template #header>
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div
                            class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2 lg:grid-cols-4"
                        >
                            <a
                                href="#"
                                class="p-4 transition-shadow border rounded-lg shadow-sm hover:shadow-lg"
                            >
                                <div class="flex items-start">
                                    <div
                                        class="flex flex-col flex-shrink-0 space-y-2"
                                    >
                                        <span class="text-gray-400">Staff</span>
                                        <span class="text-lg font-semibold"
                                            >1,854</span
                                        >
                                    </div>
                                    <div class="relative min-w-0 ml-auto h-14">
                                        <canvas id="canvasId"></canvas>
                                    </div>
                                </div>
                                <div>
                                    <span
                                        class="inline-block px-2 text-sm text-white bg-green-300 rounded mr-1"
                                        >25%</span
                                    >
                                    <span>from 2021</span>
                                </div>
                            </a>
                        </div>
                        <div class="flex justify-between">
                            <h3 class="mt-6 text-xl">People</h3>
                            <BreezeInput
                                v-model="search"
                                type="search"
                                class="w-2/3 md:w-1/2 lg:w-1/3"
                                required
                                autofocus
                            />
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
                                                        Date of Birth
                                                    </th>
                                                    <th
                                                        scope="col"
                                                        class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        SSNIT No
                                                    </th>
                                                    <th
                                                        scope="col"
                                                        class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                                    >
                                                        Role
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
                                                    v-for="person in people.data"
                                                    :key="person.id"
                                                    class="transition-all hover:bg-gray-100 hover:shadow-lg"
                                                >
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap"
                                                    >
                                                        <div
                                                            class="flex items-center"
                                                        >
                                                            <div
                                                                class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-full flex justify-center items-center"
                                                            >
                                                                {{
                                                                    person.initials
                                                                }}
                                                            </div>

                                                            <div class="ml-4">
                                                                <div
                                                                    class="text-sm font-medium text-gray-900"
                                                                >
                                                                    {{
                                                                        person.name
                                                                    }}
                                                                </div>
                                                                <div
                                                                    class="text-sm text-gray-500"
                                                                >
                                                                    {{
                                                                        person.gender
                                                                    }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap"
                                                    >
                                                        <div
                                                            class="text-sm text-gray-900"
                                                        >
                                                            {{
                                                                formatDate(
                                                                    person.dob
                                                                )
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-sm text-gray-500"
                                                        >
                                                            {{
                                                                getAge(
                                                                    person.dob
                                                                )
                                                            }}
                                                            Years
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap"
                                                    >
                                                        <div
                                                            class="text-sm text-gray-900"
                                                        >
                                                            {{ person.ssn }}
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                                    >
                                                        Staff
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
                                                    >
                                                        <Link
                                                            :href="
                                                                route(
                                                                    'person.show',
                                                                    {
                                                                        person: person.id,
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
                                        <Pagination :records="people" />
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
