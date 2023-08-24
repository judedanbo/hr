<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import Avatar from "../Person/partials/Avatar.vue";
import NoItem from "@/Components/NoItem.vue";

defineProps({
  staff: Object,
});

defineEmits(["update:modelValue"]);
</script>

<template>
  <div
    class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 w-full lg:w-2/5 py-4"
  >
    <p
      class="font-bold text-xl px-8 text-gray-700 dark:text-white tracking-wide"
    >
      <span>Staff</span>

      <span
        v-if="staff.length"
        class="text-lg text-gray-500 dark:text-white ml-2"
      >
        ({{ staff.length }})
      </span>
    </p>

    <div v-if="staff">
      <!-- {{ staff }} -->
      <table class="mt-6 w-full whitespace-nowrap text-left">
        <colgroup>
          <col class="w-full sm:w-4/12" />
          <col class="lg:w-4/12" />
          <col class="lg:w-1/12" />
          <col class="lg:w-1/12" />
        </colgroup>
        <thead class="border-b border-white/10 text-sm leading-6 text-gray-500 uppercase dark:text-white">
          <tr>
            <th
              scope="col"
              class="py-2 pl-4 pr-8 font-semibold sm:pl-6 lg:pl-8"
            >
              Staff
            </th>
            <th
              scope="col"
              class="hidden py-2 pl-0 pr-8 font-semibold sm:table-cell"
            >
              Duration at unit
            </th>
            
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          <tr v-for="sta in staff" :key="sta.id">
            <td class="py-4 pl-4 pr-8 sm:pl-6 lg:pl-8">
              <div class="flex  gap-x-4">
                <Avatar :initials="sta.initials" :imageUrl="staff.image" />
                <div class="truncate text-sm font-medium leading-6 text-gray-700 dark:text-white">
                  {{ sta.name }}
                  <div class="font-mono text-sm leading-6 text-gray-400">
                  {{ sta.rank?.name }}
                </div>
                  
                </div>
              </div>
            </td>
            <td class="hidden py-4 pl-0 pr-4 sm:table-cell sm:pr-8">
              <div class="flex flex-col gap-x-3">
                <div class="font-mono text-sm text-right leading-6 text-gray-400">
                  <time :datetime="sta.unit?.start_date_full">{{ sta.unit?.start_date }}</time>
                </div>
                <div class="font-mono text-sm text-right leading-6 text-gray-400">
                  {{ sta.unit?.duration }} 
                </div>
                
              </div>
            </td>
           
           
            <td
              class="hidden py-4 pl-0 pr-4 text-right text-sm leading-6 text-gray-400 sm:table-cell sm:pr-6 lg:pr-8"
            >
              <!-- <time :datetime="item.dateTime">{{ item.date }}</time> -->
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <NoItem v-else :name="staff" />
  </div>
</template>
