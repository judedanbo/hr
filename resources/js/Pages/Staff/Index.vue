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
    staff: Object,
    filters: Object,
});

let search = ref(props.filters.search);

watch(
    search,
    debounce(function (value) {
        Inertia.get(
            route("staff.index"),
            { search: value },
            { preserveState: true, replace: true }
        );
    }, 300)
);

let openStaff = (staff) => {
    console.log(staff);
    Inertia.visit(route("staff.show", { staff: staff }));
};

let formatDate = (dateString) => {
    const date = new Date(dateString);
    return format(date, "dd MMMM, yyyy");
    // return new Intl.DateTimeFormat("en-GB", { dateStyle: "full" }).format(date);
};

let getAge = (dateString) => {
    const date = new Date(dateString);
    // console.log(Date);

    return differenceInYears(new Date(), date);
};
let BreadCrumpLinks = [
    {
        name: "Staff",
        url: "",
    },
];
</script>

<template>
    <Head title="Staff" />

    <MainLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Staff
            </h2>
        </template>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <BreadCrumpVue :links="BreadCrumpLinks" />
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 border-b border-gray-200">
                    <div class="sm:flex items-center justify-between my-2">
                        <InfoCard title="Staff" :value="staff.total" link="#" />
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
                                placeholder="Search staff..."
                            />
                        </div>
                    </div>

                    <div class="flex flex-col mt-6">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div
                                class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
                            >
                                <div
                                    v-if="staff.total > 0"
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
                                                    Employment
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                                >
                                                    Rank
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                                >
                                                    Current Unit
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white divide-y divide-gray-200"
                                        >
                                            <tr
                                                v-for="person in staff.data"
                                                :key="person.id"
                                                @click="openStaff(person.id)"
                                                class="cursor-pointer transition-all hover:bg-gray-100 hover:shadow-lg"
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

                                                        ({{
                                                            getAge(person.dob)
                                                        }}
                                                        Years )
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap"
                                                >
                                                    <div
                                                        class="text-sm text-gray-900"
                                                    >
                                                        <div
                                                            class="text-sm text-gray-500"
                                                        >
                                                            {{
                                                                formatDate(
                                                                    person.hire_date
                                                                ) +
                                                                " (" +
                                                                getAge(
                                                                    person.hire_date
                                                                ) +
                                                                " years) "
                                                            }}
                                                        </div>

                                                        {{ person.file_number }}
                                                        /
                                                        {{
                                                            person.staff_number
                                                        }}
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                                >
                                                    {{ person.rank.name }}
                                                    <p>
                                                        {{
                                                            formatDate(
                                                                person.rank
                                                                    .start_date
                                                            )
                                                        }}
                                                        ({{
                                                            getAge(
                                                                person.rank
                                                                    .start_date
                                                            ) + " years"
                                                        }})
                                                    </p>
                                                </td>
                                                <td
                                                    class="px-6 py-4 text-sm font-medium whitespace-nowrap"
                                                >
                                                    {{
                                                        person.unit.name.substring(
                                                            20
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <Pagination :records="staff" />
                                </div>

                                <NoItem v-else name="Staff" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
