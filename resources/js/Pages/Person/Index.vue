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
import InfoCard from "@/Components/InfoCard.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import NoItem from "@/Components/NoItem.vue";

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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                People
            </h2>
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 border-b border-gray-200">
                    <div class="sm:flex items-center justify-between my-2">
                        <InfoCard
                            title="People"
                            :value="people.total"
                            link="#"
                        />
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
                                placeholder="Search People..."
                            />
                        </div>
                    </div>

                    <div class="flex flex-col mt-6">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div
                                class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
                            >
                                <div
                                    v-if="people.total > 0"
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
                                                        {{ getAge(person.dob) }}
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
                                                    <Link
                                                        v-if="
                                                            person.institution
                                                        "
                                                        :class="
                                                            person.institution
                                                                .status ==
                                                            'Active'
                                                                ? 'bg-green-300 hover:bg-gray-400'
                                                                : 'bg-gray-300 hover:bg-gray-400'
                                                        "
                                                        class="text-gray-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-xs px-2 py-1 text-center mr-2 mb-2"
                                                    >
                                                        {{
                                                            person.institution
                                                                .status
                                                        }}
                                                        Staff
                                                    </Link>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'staff.show',
                                                                {
                                                                    staff: person
                                                                        .dependent
                                                                        .staff_id,
                                                                }
                                                            )
                                                        "
                                                        v-if="person.dependent"
                                                        class="text-white bg-orange-700 hover:bg-orange-800 focus:outline-none focus:ring-4 focus:ring-orange-300 font-medium rounded-full text-xs px-2 py-1 text-center mr-2 mb-2 dark:bg-orange-600 dark:hover:bg-orange-700 dark:focus:ring-orange-800"
                                                    >
                                                        Dependent
                                                    </Link>
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

                                <NoItem v-else name="Person" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
