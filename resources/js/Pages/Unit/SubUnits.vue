<script setup>
import { Link } from "@inertiajs/inertia-vue3";

import NoItem from "@/Components/NoItem.vue";

defineProps({
  type: String,
  subs: Object,
  searchText: String,
});

defineEmits(["update:modelValue"]);
</script>

<template>
  
  <div
    v-if="type != 'Unit'"
    class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 w-full xl:w-2/5 py-4 flex-none"
  >
    <p
      class="font-bold text-xl px-8 py-4 text-gray-700 dark:text-white tracking-wide"
    >
      <span v-text="type"></span>

      <span
        v-if="subs.length"
        class="text-lg text-gray-500 dark:text-white ml-2"
      >
        ({{ subs.subs }})
      </span>
    </p>
    
    <div v-if="subs">
      <div class="mt-8 flow-root ">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8 h-96 overflow-scroll">
        <table class="min-w-full divide-y divide-gray-300">
          <thead>
            <tr>
              <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-8">Sub unit</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50">Staff</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50">Sub units</th>
              
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-700">
            <tr v-for="(subUnit, index) in subs.subs" :key="index" class="even:bg-gray-50 dark:even:bg-gray-500">
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-8">{{subUnit.name}}</td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200">{{ subUnit.staff_count }}</td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200">{{ subUnit.subs }}</td>             
            </tr>

          </tbody>
        </table>
      </div>
    </div>
  </div>

      <!-- <div
        v-for="(subUnit, index) in subs.subs"
        class="block rounded-lg bg-secondary shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-secondary-600 w-full"
      >
        <div class="p-6">
          <h5
            class="mb-2 text-xl font-medium leading-tight text-gray-800 dark:text-neutral-50 pl-4"
          >
            <Link :href="route('unit.show', { unit: subUnit.id })"
              >{{ subUnit.name }}
            </Link>
          </h5>
          <dl
            class="mt-2 grid grid-cols-2 justify-evenly gap-0.5 overflow-hidden rounded-2xl text-center sm:grid-cols-2"
          >
            <div class="flex flex-col bg-gray-100 dark:bg-white/5 px-8 py-4">
              <dt class="text-sm font-semibold leading-6 text-gray-500">
                {{ "Units" }}
              </dt>
              <dd
                class="order-first text-2xl font-semibold tracking-tight text-gray-600 dark:text-white"
              >
                {{ subUnit.subs }}
              </dd>
            </div>
            <div class="flex flex-col bg-gray-100 dark:bg-white/5 px-8 py-4">
              <dt class="text-sm font-semibold leading-6 text-gray-500">
                {{ "Staff" }}
              </dt>
              <dd
                class="order-first text-2xl font-semibold tracking-tight text-gray-600 dark:text-white"
              >
                {{ subUnit.staff_count }}
              </dd>
            </div>
          </dl>
        </div>
      </div> -->
    </div>
    <NoItem v-else :name="type" />
  </div>
</template>
