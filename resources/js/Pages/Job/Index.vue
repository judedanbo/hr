<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import NoItem from "@/Components/NoItem.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import PageHeader from "@/Components/PageHeader.vue";
import BreezeButton from "@/Components/Button.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/Modal.vue";
import AddRank from "./partials/Add.vue";

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

let props = defineProps({
  jobs: Object,
  filters: Object,
});

let previousJobs = ref([{
    value: null,
    label: "Select previous Rank",
}])
props.jobs.data.map((job) => {
    previousJobs.value.push( {
        value: job.id,
        label: job.name,
    });
    }
);

let BreadCrumpLinks = [
  {
    name: "Ranks",
  },
];

let search = ref(props.filters.search);

watch(
  search,
  debounce(function (value) {
    Inertia.get(
      route("job.index"),
      { search: value },
      { preserveState: true, replace: true, preserveScroll: true }
    );
  }, 300)
);
</script>

<template>
  <Head title="Ranks" />

  <MainLayout>
    <template #header>
      <PageHeader name="Ranks" />
    </template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-2 border-b border-gray-200">
          <div
            class="grid grid-cols-1 gap-6 mt-2 md:grid-cols-2 lg:grid-cols-4"
          ></div>
          <BreadCrumpVue :links="BreadCrumpLinks" />
          <div class="sm:flex items-center justify-between my-2">
            <FormKit
              v-model="search"
              prefix-icon="search"
              type="search"
              placeholder="Search ranks..."
              autofocus
            />
            <BreezeButton @click="toggle()">Add New</BreezeButton>
          </div>

          <div v-if="jobs.total > 0" class="flex flex-col mt-2">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
              <div
                class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
              >
                <div
                  class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
                >
                  <table
                    class="min-w-full overflow-x-scroll divide-y divide-gray-200"
                  >
                    <thead class="bg-gray-50">
                      <tr class="dark:bg-gray-700">
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-100 "
                        >
                          Name
                        </th>

                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-100 uppercase"
                        >
                          Staff
                        </th>

                        <th role="col" class="relative px-6 py-3">
                          <span class="sr-only">Edit</span>
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr
                        v-for="job in jobs.data"
                        :key="job.id"
                        class="transition-all hover:bg-gray-100 hover:shadow-lg dark:bg-gray-600 dark:hover:bg-gray-700"
                      >
                        <td class="px-6 py-2 whitespace-nowrap">
                          <div class="flex items-center">
                            <div
                              class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-full flex justify-center items-center"
                            ></div>

                            <div class="ml-4">
                              <div class="text-sm font-medium text-gray-900 dark:text-gray-50">
                                {{ job.name }}
                                {{
                                  job.abbreviation
                                    ? "(" + job.abbreviation + ")"
                                    : ""
                                }}
                              </div>
                              <div class="text-sm text-gray-500"></div>
                            </div>
                          </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900 dark:text-gray-50 text-center">
                            {{ job.staff.toLocaleString() }}
                          </div>
                        </td>

                        <td
                          class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
                        >
                          <Link
                            :href="
                              route('job.show', {
                                job: job.id,
                              })
                            "
                            class="text-green-600 hover:text-green-900 dark:text-gray-50 dark:hover:text-green-400"
                            >Show</Link
                          >
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <Pagination :records="jobs" />
                </div>
              </div>
            </div>
          </div>
          <NoItem v-else name="Rank" />
        </div>
      </div>
    </div>
    <Modal @close="toggle()" :show="openAddDialog">
        <AddRank @formSubmitted="toggle()" :jobs="previousJobs" />
    </Modal>
  </MainLayout>
</template>
