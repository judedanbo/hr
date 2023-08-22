<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { formatDistance } from "date-fns";

import { useToggle } from "@vueuse/core";
import { ref } from "vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { EllipsisVerticalIcon, PlusIcon } from "@heroicons/vue/20/solid";
// import Avatar from "../Person/partials/Avatar.vue";

let showPromotionForm = ref(false);
let showTransferForm = ref(false);

let togglePromotionForm = useToggle(showPromotionForm);
let toggleTransferForm = useToggle(showTransferForm);
let getAge = (dateString) => {
  const date = new Date(dateString);
  return formatDistance(date, new Date(), { addSuffix: true });
};

let props = defineProps({
  institution: Object,
  staff: Array,
  status: Object,
});

let search = ref('')

// let BreadcrumbLinks = [
//   { name: "Staff", url: "/staff" },
//   { name: props.person.name, url: "/" },
// ];
</script>
<template>
  <Head title="Status" />

  <MainLayout>
    <main>
      <header
        class="relative isolate pt-4 border dark:border-gray-600 rounded-lg"
      >
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
          <pre>

            {{ staff }}
          </pre>
        </div>
      </header>

      <div class="overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 border-b border-gray-200">
          <div class="sm:flex items-center justify-between my-2">
            <FormKit
              v-model="search"
              prefix-icon="search"
              type="search"
              placeholder="Search institutions..."
              autofocus
            />
            <!-- <InfoCard title="Staff" :value="staff.total" link="#" /> -->

            <!-- <BreezeButton @click="toggle()">Add New Staff</BreezeButton> -->
            <a
              @click.prevent="toggle()"
              href="#"
              class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
            >
              <PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
              New Staff
            </a>
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
                    <thead class="bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
                        >
                          Name
                        </th>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
                        >
                          Date of Birth
                        </th>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
                        >
                          Employment
                        </th>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
                        >
                          Rank
                        </th>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
                        >
                          Current Unit
                        </th>
                      </tr>
                    </thead>
                    <tbody
                      class="bg-white dark:bg-gray-500 divide-y divide-gray-200"
                    >
                      <tr
                        v-for="person in staff"
                        :key="person.id"
                        @click="openStaff(person.id)"
                        class="cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-lg"
                      >
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="flex items-center">
                            <!-- <div
                              class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-full flex justify-center items-center"
                            >
                              {{ person.initials }}
                            </div> -->

                            <!-- <Avatar
                              :initials="person.initials"
                              :image-url="person.image"
                            /> -->
                            <div class="ml-4">
                              <div
                                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                              >
                                {{ person.name }}
                              </div>
                              <div
                                class="text-xs text-gray-500 dark:text-gray-100"
                              >
                                {{ person.gender }}
                                |
                                {{ person.staff_number }}

                                {{
                                  person.file_number
                                    ? " / " + person.file_number
                                    : ""
                                }}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900 dark:text-gray-100">
                            {{ formatDate(person.dob) }}
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-100">
                            {{
                              formatDistanceStrict(
                                new Date(person.dob),
                                new Date()
                              )
                            }}
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900 dark:text-gray-100">
                            {{ formatDate(person.hire_date) }}
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-100">
                            {{
                              formatDistanceStrict(
                                new Date(person.hire_date),
                                new Date(),
                                {
                                  addSuffix: true,
                                }
                              )
                            }}
                          </div>
                        </td>
                        <td
                          class="px-6 py-4 text-sm text-gray-500 dark:text-gray-100 whitespace-nowrap"
                          :title="
                            getAge(person.current_rank?.start_date) + ' years'
                          "
                        >
                          <div v-if="person.current_rank">
                            <div
                              class="text-sm text-gray-900 dark:text-gray-100"
                            >
                              {{ person.current_rank.name }}
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-100">
                              {{ formatDate(person.current_rank.start_date) }}

                              <span
                                v-if="person.current_rank.remarks"
                                class="text-green-800 dark:text-gray-100 font-semibold"
                              >
                                -
                                {{ person.current_rank.remarks }}
                              </span>
                            </p>
                          </div>
                        </td>
                        <td
                          class="px-6 py-4 text-sm font-medium whitespace-nowrap dark:text-gray-100"
                          :title="
                            getAge(person.current_unit?.start_date) + ' years'
                          "
                        >
                          <div v-if="person.current_unit">
                            <div>
                              {{ person.current_unit?.name }}
                            </div>
                            <div>
                              {{ formatDate(person.current_unit.start_date) }}
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <!-- <Pagination :records="staff" /> -->
                </div>
                <!-- <NoItem v-else name="Staff" /> -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </MainLayout>
</template>
