<script setup>
import { watch, ref, computed } from 'vue'
import { format, formatDistanceStrict } from 'date-fns'
import { Inertia } from "@inertiajs/inertia";
import BreezeInput from "@/Components/Input.vue";
import { Link } from '@inertiajs/inertia-vue3';

defineEmits(["update:modelValue"]);

let props = defineProps({
    promotions: Array,

})


const formatDate = (date) => {
    if (date === null) {
        return ''
    }
    return format(new Date(date), 'dd MMM, yyyy')
}

const formatDistance = (date) => {
    if (date === null) {
        return ''
    }
    return formatDistanceStrict(new Date(date), new Date(props.promotions[0].now), { addSuffix: true })
}

const selectedStaff = ref([])

const fullName = (user) => {
    return `${user.first_name} ${user.other_name ?? ''} ${user.surname}`
}

const checked = ref(false)
const indeterminate = computed(() => selectedStaff.value.length > 0 && selectedStaff.value.length < props.promotions.length)
</script>
<template>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto py-8">
                <!-- <h1 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50">April 2022 </h1> -->
                <!-- <p class="mt-2 text-sm text-gray-700 dark:text-gray-50">A list of all who were last promoted at least 3 -->
                <!-- years ago. Ordered by last promotion date</p> -->
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0  flex space-x-4">
                <BreezeInput @input="$emit('update:modelValue', $event.target.value)" type="search"
                    class="w-full pl-8 bg-white border-0" required autofocus placeholder="Search Staff..." />
                <button type="button"
                    class="block rounded-md bg-green-600 dark:bg-gray-700 px-3 py-1.5 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 w-48">
                    Export Data
                </button>
            </div>
        </div>
        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="relative">
                        <div v-if="selectedStaff.length > 0"
                            class="absolute left-14 top-0 flex h-12 items-center space-x-3 bg-white dark:bg-gray-800 sm:left-12">
                            <button type="button"
                                class="inline-flex items-center rounded bg-white dark:bg-gray-800 px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white dark:text-gray-50">Bulk
                                edit</button>
                            <button type=" button"
                                class="inline-flex items-center rounded bg-white dark:bg-gray-800 px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white dark:text-gray-50">Delete
                                all</button>``
                        </div>
                        <table class="min-w-full table-fixed divide-y divide-gray-300">
                            <thead>
                                <tr>
                                    <th scope="col" class="relative px-7 sm:w-12 sm:px-6 text-left ">
                                        <input
                                            @change="selectedStaff = $event.target.checked ? promotions.map((p) => p.staff_number) : []"
                                            type="checkbox" :indeterminate="indeterminate"
                                            :checked="indeterminate || selectedStaff.length === promotions.length"
                                            class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-green-600 dark:text-gray-900 focus:ring-green-600 dark:focus:ring-gray-600" />
                                    </th>
                                    <th scope="col"
                                        class="min-w-[12rem] py-3.5 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        Name
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        Current Rank
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        Last Promotion
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        Current Posting
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        Retirement Date
                                    </th>

                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-3">
                                        <span class="sr-only">Edit</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-700">
                                <tr v-for="promotion in promotions" :key="promotion.staff_number"
                                    :class="[selectedStaff.includes(promotion.staff_number) && 'bg-green-50 dark:bg-gray-500']">
                                    <td class="relative px-7 sm:w-12 sm:px-6">
                                        <div v-if="selectedStaff.includes(promotion.staff_number)"
                                            class="absolute inset-y-0 left-0 w-0.5 bg-green-600 dark:bg-gray-100">&nbsp;
                                        </div>
                                        <input v-model="selectedStaff" :value="promotion.staff_number" type="checkbox"
                                            class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-green-600 dark:text-gray-600 focus:ring-green-600" />
                                    </td>

                                    <td
                                        :class="['whitespace-nowrap py-4 pr-3 text-sm font-medium', selectedStaff.includes(promotion.staff_number) ? 'text-green-600' : 'text-gray-900 dark:text-gray-50']">
                                        <div class="font-medium text-gray-900 dark:text-gray-50">
                                            <Link :href="route('staff.show', { staff: promotion.id })">
                                            {{ fullName(promotion) }}
                                            </Link>
                                        </div>
                                        <div class="mt-1 text-gray-500 dark:text-gray-200">
                                            {{ promotion.staff_number }} | {{ promotion.file_number }}
                                        </div>

                                    </td>

                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200">

                                        <div class="font-medium text-gray-900 dark:text-gray-50">
                                            {{ promotion.rank_name }}</div>
                                        <div class="mt-1 text-gray-500 dark:text-gray-200">
                                            {{ promotion.remarks }}
                                        </div>

                                    </td>
                                    <!-- <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200">
                                        {{ promotion.staff_number }}
                                    </td> -->
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200 ">

                                        <div class="font-medium text-gray-900 dark:text-gray-50">
                                            {{ formatDate(promotion.start_date) }}
                                        </div>
                                        <div class="mt-1 text-gray-500 dark:text-gray-200">
                                            {{ formatDistance(promotion.start_date) }}
                                        </div>

                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200 ">

                                        <div v-if="promotion.unit.length > 0" class="font-medium text-gray-900 dark:text-gray-50">
                                            {{ promotion.unit.name }}
                                        </div>
                                        <div v-if="promotion.unit.length > 0" class="mt-1 text-gray-500 dark:text-gray-200">
                                            {{ formatDistance(promotion.unit.start_date) }}
                                        </div>
                                        <div v-else> no posting</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200 ">

                                        <div  class="font-medium text-gray-900 dark:text-gray-50">
                                            {{ formatDate(promotion.retirement_date) }}
                                        </div>
                                        <div class="mt-1 text-gray-500 dark:text-gray-200">
                                            {{ formatDistance(promotion.retirement_date) }}
                                        </div>
                                        
                                    </td>
                                    <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-3">
                                        <a href="#"
                                            class="text-green-600 dark:text-gray-100 hover:text-green-900 dark:hover:text-gray-50">
                                            Show history<span class="sr-only">, {{ promotion.name }}</span>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template> 
