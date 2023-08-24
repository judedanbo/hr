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
    class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 w-full lg:w-2/5 py-4"
  >
    <p
      class="font-bold text-xl px-8 text-gray-700 dark:text-white tracking-wide"
    >
      <span v-text="type"></span>

      <span
        v-if="subs.length"
        class="text-lg text-gray-500 dark:text-white ml-2"
      >
        ({{ subs.length }})
      </span>
    </p>
    
    <div v-if="subs">

      <div
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
      </div>
    </div>
    <NoItem v-else :name="type" />
  </div>
</template>
