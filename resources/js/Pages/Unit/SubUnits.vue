<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";

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
      <div class="mt-2 relative mx-8">
        <!-- <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                    <span class="text-gray-500 sm:text-sm">
                        <MagnifyingGlassIcon class="w-4 h-4" />
                    </span>
                </div> -->
        <!-- <BreezeInput
                    :modelValue="searchText"
                    @input="$emit('update:modelValue', $event.target.value)"
                    type="search"
                    class="w-full pl-8 bg-slate-100 border-0"
                    required
                    autofocus
                    :placeholder="
                        type == 'Department'
                            ? 'Search divisions...'
                            : 'Search units...'
                    "
                /> -->
      </div>
      <div
        v-for="(subUnit, index) in subs.subs"
        class="block rounded-lg bg-secondary shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:bg-secondary-600 w-full"
      >
        <div class="p-6">
          <h5
            class="mb-2 text-xl font-medium leading-tight text-gray-800 dark:text-neutral-50"
          >
            <Link :href="route('unit.show', { unit: subUnit.id })"
              >{{ subUnit.name }}
            </Link>
          </h5>
          <div class="flex justify-between">
            <dl
              class="mt-2 grid grid-cols-1 gap-0.5 overflow-hidden rounded-2xl text-center sm:grid-cols-2"
            >
              <div
                class="flex flex-col bg-white/5 p-8"
              >
                <dt class="text-sm font-semibold leading-6 text-gray-300">
                  {{ 'Units' }}
                </dt>
                <dd
                  class="order-first text-2xl font-semibold tracking-tight text-white"
                >
                  {{ subUnit.subs  }}
                </dd>
              </div>
              <div
                class="flex flex-col bg-white/5 p-8"
              >
                <dt class="text-sm font-semibold leading-6 text-gray-300">
                  {{ 'Staff' }}
                </dt>
                <dd
                  class="order-first text-2xl font-semibold tracking-tight text-white"
                >
                  {{ subUnit.staff_count  }}
                </dd>
              </div>
            </dl>
            <!-- <Link
              :href="route('unit.show', { unit: subUnit.id })"
              class="truncate hover:underline text-gray-500 dark:text-gray-300"
              >Sub Units {{ subUnit.staff_count }}
            </Link>
            <Link
              :href="route('unit.show', { unit: subUnit.id })"
              class="truncate hover:underline text-gray-50"
              >Staff: {{ subUnit.subs }}
            </Link> -->
          </div>
        </div>
      </div>
      <!-- <ul class="px-8 pb-6 max-h-96 overflow-y-auto">
        <li
          v-for="(subUnit, index) in subs.subs"
          :key="index"
          class="flex justify-between gap-x-6 py-2.5"
        >
          <div class="flex min-w-0 gap-x-4">
            <div class="min-w-0 flex-auto">
              <p
                class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50"
              >
                <Link
                  :href="route('unit.show', { unit: subUnit.id })"
                  class="hover:underline"
                  >{{ subUnit.name }}</Link
                >
              </p>
              <p
                class="mt-1 flex text-xs leading-5 text-gray-500 dark:text-gray-300"
              >
                <Link
                  :href="route('unit.show', { unit: subUnit.id })"
                  class="truncate hover:underline"
                  >Staff {{ subUnit.staff_count }}
                </Link>
              </p>
            </div>
          </div>
          <div class="flex shrink-0 items-center gap-x-6">
            <div class="hidden sm:flex sm:flex-col sm:items-end">
              <p class="text-sm leading-6 text-gray-900 dark:text-gray-50">
                {{ subUnit.rank }}
              </p>
              <p
                class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-300"
              >
                Sub Units
                <time :datetime="subUnit.staff_count">{{
                  subUnit.staff_count
                }}</time>
              </p>
            </div>
            <Menu as="div" class="relative flex-none">
              <MenuButton
                class="-m-2.5 block p-2.5 text-gray-500 hover:text-gray-900 dark:text-gray-50"
              >
                <span class="sr-only">Open options</span>
                <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
              </MenuButton>
              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems
                  class="absolute right-0 z-10 mt-2 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
                >
                  <MenuItem v-slot="{ active }">
                    <Link
                      href="#"
                      :class="[
                        active ? 'bg-gray-50' : '',
                        'block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50',
                      ]"
                      >View profile<span class="sr-only"
                        >, {{ subUnit.name }}</span
                      ></Link
                    >
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <a
                      :href="
                        route('unit.show', {
                          unit: subUnit.id,
                        })
                      "
                      >Message<span class="sr-only"
                        >, {{ subUnit.name }}</span
                      ></a
                    >
                  </MenuItem>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </li>
      </ul> -->
    </div>
    <NoItem v-else :name="type" />
  </div>
</template>
