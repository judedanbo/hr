<script setup>
import { format, differenceInYears } from "date-fns";
import { Link } from "@inertiajs/inertia-vue3";
import {
  CalendarDaysIcon,
  UserPlusIcon,
  FlagIcon,
  IdentificationIcon
} from "@heroicons/vue/20/solid";
defineProps({
  transfers: Array,
});
const formattedDob = (dob) => {
  return new Date(dob).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
};

let getAge = (dateString) => {
    const date = new Date(dateString);
    return differenceInYears(new Date(), date);
    };
        

</script>
<template>
  <!-- Transfer History -->
  <main >
    <h2 class="sr-only">Transfer History</h2>
    <div class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-300/80">
      <dl class="flex flex-wrap">
        <div class="flex-auto pl-6 pt-6">
          <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">
            Transfer History
          </dt>
        </div>
        <div class="flex-none self-end px-6 pt-4">
         
         <Link
           :href="route('staff.index')"
           class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-200"
         >
           Transfer
         </Link>
       </div>
        
        <div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
      <table v-if="transfers.length > 0" class="min-w-full">
        <colgroup>
          <col class="w-full" />
          <col class="sm:w-1/6" />
          <col class="sm:w-1/6" />
          <col class="sm:w-1/6" />
        </colgroup>
        <thead class="border-b border-gray-300 text-gray-900">
          <tr>
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Position</th>
            <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">Start</th>
            <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">End</th>
            <th scope="col" class="py-3.5 pl-3 pr-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">Duration</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="transfer in transfers" :key="transfer.id" class="border-b border-gray-200">
            <td class="max-w-0 py-5 pl-4 pr-3 text-sm sm:pl-0">
              <div class="font-medium text-gray-900">{{ transfer.name }}</div>
              <div class="mt-1 truncate text-gray-500">{{ transfer.remarks }}</div>
            </td>
            <td class="hidden px-3 py-5 text-right text-sm text-gray-500 sm:table-cell">{{ transfer.start_date }}</td>
            <td class="hidden px-3 py-5 text-right text-sm text-gray-500 sm:table-cell">{{ transfer.end_date }}</td>
            <!-- <td class="py-5 pl-3 pr-4 text-right text-sm text-gray-500 sm:pr-0">{{ transfer.price }}</td> -->
          </tr>
        </tbody>
       
      </table>
      <div v-else class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center ">No transfers found.</div>
    </div>
      </dl>
     
    </div>
  </main>
</template>
