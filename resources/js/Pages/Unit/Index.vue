<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import InfoCard from "@/Components/InfoCard.vue";
import NoItem from "@/Components/NoItem.vue";
import BreezeButton from "@/Components/Button.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/Modal.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import AddUnit from "./partials/Add.vue";

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

let props = defineProps({
  units: Object,
  filters: Object,
  unit_types: Array,
});

let search = ref(props.filters.search);

let parentUnits = ref([
  {
    value: null,
    label: "Select Parent Unit",
  },
]);
props.units.data.map((unit) => {
  parentUnits.value.push({
    value: unit.id,
    label: unit.name,
  });
});

watch(
  search,
  debounce(function (value) {
    Inertia.get(
      route("unit.index"),
      { search: value },
      { preserveState: true, replace: true }
    );
  }, 300)
);

let openUnit = (unit) => {
  Inertia.visit(route("unit.show", { unit: unit }));
};

let BreadCrumpLinks = [
  {
    name: props.units.data[0].institution.name,
    url: route("institution.show", {
      institution: props.units.data[0].institution.id,
    }),
  },
  {
    name: "Departments",
  },
];
</script>

<template>
  <Head title="Departments" />

  <MainLayout>
    <template #header>
      <h2
        class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
      >
        Departments
      </h2>
    </template>
    <div class="overflow-hidden shadow-sm sm:rounded-lg">
      <div class="p-6 border-b border-gray-200">
        <BreadCrumpVue :links="BreadCrumpLinks" />
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4"></div>
        <div class="sm:flex items-center justify-between my-2">
          <FormKit
            v-model="search"
            prefix-icon="search"
            type="search"
            placeholder="Search department/unit/sections..."
            autofocus
          />
          <InfoCard title="Units" :value="units.total" />
          <BreezeButton @click="toggle()">Add New</BreezeButton>
        </div>
        <!-- {{ units.data[0].institution }} -->
        <div v-if="units.total > 0" class="flex flex-col mt-2">
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
                  <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th
                        scope="col"
                        class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-100 uppercase"
                      >
                        Name
                      </th>
                      <th
                        scope="col"
                        class="px-6 py-3 text-xs font-medium tracking-wider text-gray-500 dark:text-gray-100 uppercase text-right"
                      >
                        Units
                      </th>
                      <th
                        scope="col"
                        class="px-6 py-3 text-xs font-medium tracking-wider text-gray-500 dark:text-gray-100 uppercase text-right"
                      >
                        Staff
                      </th>
                    </tr>
                  </thead>
                  <tbody
                    class="bg-white dark:bg-gray-600 divide-y divide-gray-200"
                  >
                    <tr
                      @click="openUnit(unit.id)"
                      v-for="unit in units.data"
                      :key="unit.id"
                      class="cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-400 hover:shadow-lg"
                    >
                      <td class="px-6 py-2 whitespace-nowrap">
                        <div class="flex items-center">
                          <div
                            class="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-full flex justify-center items-center"
                          ></div>

                          <div class="ml-4">
                            <div
                              class="text-sm font-medium text-gray-900 dark:text-gray-100"
                            >
                              {{ unit.name }}
                            </div>
                            <div
                              class="text-sm text-gray-500 dark:text-gray-100"
                            ></div>
                          </div>
                        </div>
                      </td>

                      <td
                        class="px-6 py-4 text-sm font-medium whitespace-nowrap dark:text-gray-50 text-right"
                      >
                        {{ unit.units.toLocaleString() }}
                      </td>
                      <td
                        class="px-6 py-4 text-sm font-medium whitespace-nowrap dark:text-gray-50 text-right"
                      >
                        {{ unit.staff.toLocaleString() }}
                      </td>
                    </tr>
                  </tbody>
                </table>
                <Pagination :records="units" />
              </div>
            </div>
          </div>
        </div>
        <NoItem v-else name="Department" />
      </div>
    </div>
    <Modal @close="toggle()" :show="openAddDialog">
      <AddUnit
        :units="parentUnits"
        :types="unit_types"
        :institution="units.data[0].institution"
      />
    </Modal>
  </MainLayout>
</template>
