<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import Avatar from "../Person/partials/Avatar.vue";
import NoItem from "@/Components/NoItem.vue";

defineProps({
  unit: {
    type: Object,
    required: true,
  }
});

defineEmits(["update:modelValue"]);
</script>

<template>
  <div
    class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 w-full xl:w-2/5 py-4 flex-grow"
  >
    <p
      class="font-bold text-xl px-8 py-4 text-gray-700 dark:text-white tracking-wide"
    >
      <span>Staff</span>

      <span
        class="text-lg text-gray-500 dark:text-white ml-2"
      >
        ({{ unit.staff_number }})
      </span>
    </p>

    <div v-if="unit.staff">
      <div class=" h-96 overflow-x-hidden overflow-y-scroll ">
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
              Rank
            </th>
            <th
              scope="col"
              class="hidden py-2 pl-0 pr-8 font-semibold sm:table-cell"
            >
              Unit
            </th>
            
          </tr>
        </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="sta in unit.staff" :key="sta.id" class="hover:bg-gray-100">
              <td class="py-4 pl-4 pr-8 sm:pl-6 lg:pl-8">
                <div class="flex  gap-x-4">
                  <Avatar :initials="sta.initials" :imageUrl="sta.image" />
                  <div class="truncate text-sm font-medium leading-6 text-gray-700 dark:text-white">
                    {{ sta.name }}
                    <div class="font-mono text-sm leading-6 text-gray-400">
                    First Appointment: {{ sta.hire_date }}
                  </div>
                    <div class="font-mono text-sm leading-6 text-gray-400">
                    Staff No.: {{ sta.staff_number }} / File No.: {{ sta.file_number }}
                  </div>
                    
                  </div>
                </div>
              </td>
              <td class="hidden xl:block">
                <div class="font-mono text-sm leading-6 text-gray-400">
                    {{ sta.rank?.name }}
                  </div>
                <div class="font-mono text-sm leading-6 text-gray-400">
                    {{ sta.rank?.start_date }}
                  </div>
                <div class="font-mono text-sm leading-6 text-gray-400">
                    {{ sta.rank?.remarks }}
                  </div>
              </td>
              <td class="hidden py-4 pl-0 pr-4 sm:table-cell sm:pr-8">
                <div class="flex flex-col gap-x-3">
                  <div class="font-mono text-sm leading-6 text-gray-400">
                    <p>{{ sta.unit.name }}</p>
                    <time :datetime="sta.unit?.start_date_full">{{ sta.unit?.start_date }}</time>
                  </div>
                  <div class="font-mono text-sm leading-6 text-gray-400">
                    {{ sta.unit?.duration }} 
                  </div>
                  
                </div>
              </td>
             
             
              <td
                class="hidden py-4 pl-0 pr-4 text-sm leading-6 text-gray-400 sm:table-cell sm:pr-6 lg:pr-8"
              >
                <!-- <time :datetime="item.dateTime">{{ item.date }}</time> -->
              </td>
            </tr>
          </tbody>
        </table>
        </div>
    </div>
    <NoItem v-else :name="unit.staff" />
  </div>
</template>
