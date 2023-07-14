<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce"; 
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { PlusIcon } from "@heroicons/vue/24/outline";
import PageHeader from "@/Components/PageHeader.vue";
import { useToggle } from "@vueuse/core";
import { format } from "date-fns";
import Modal from "@/Components/Modal.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Create from "./Create.vue";
import Edit from "./Edit.vue";
import Delete from "./Delete.vue";
import FlyoutMenu from "@/Components/FlyoutMenu.vue";

let props = defineProps({
  institutions: Object,
  filters: Object,
});

const form = useForm({
  name: null,
  abbreviation: null,
  start_date: format(new Date(), "yyyy-MM-dd"),
  institution_id: null,
});

let selectedModel = ref(null);

let openCreateModal = ref(false);
let openEditModal = ref(false);
let openDeleteModal = ref(false);

let toggleCreateModal = useToggle(openCreateModal);
let toggleEditModal = useToggle(openEditModal);
let toggleDeleteModal = useToggle(openDeleteModal);

let displayEditModal = ($event, id) => {
  // console.log(props.institutions.data)
  selectedModel.value = props.institutions.data.filter(
    (institution) => institution.id == id
  );
  // console.log(id)
  // console.log($event.target)
  toggleEditModal();
};
let displayDeleteModal = ($event, id) => {
  selectedModel.value = props.institutions.data.filter(
    (institution) => institution.id == id
  );
  // console.log(id)
  // console.log($event.target)
  toggleDeleteModal();
};
const submitForm = () => {
  form.post(route("institution.store"), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      toggleCreateModal();
    },
  });
};

let search = ref(props.filters.search);

watch(
  search,
  debounce(function (value) {
    Inertia.get(
      route("institution.index"),
      { search: value },
      { preserveState: true, replace: true, preserveScroll: true }
    );
  }, 300)
);

let BreadCrumpLinks = [
  {
    name: "Institutions",
  },
];
</script>

<template>
  <Head title="Institutions" />

  <MainLayout>
    <template #header>
      <PageHeader name="Institutions" />
    </template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
      <div
        class="bg-gray-100 dark:bg-gray-600 overflow-hidden shadow-sm sm:rounded-lg"
      >
        <div class="p-4">
          <BreadCrumpVue :links="BreadCrumpLinks" />
          <div class="flex justify-center items-center">
            <FormKit
              v-model="search"
              prefix-icon="search"
              type="search"
              placeholder="Search institutions..."
              autofocus
            />
            <a
              @click.prevent="toggleCreateModal()"
              href="#"
              class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
            >
              <PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
              New Institutions
            </a>
          </div>
          <div class="flex flex-col mt-2">
            <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
              <div
                class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
              >
                <div class="overflow-hidden rounded-md shadow-md">
                  <table
                    v-if="institutions.total > 0"
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
                          Departments
                        </th>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
                        >
                          Divisions
                        </th>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
                        >
                          Units
                        </th>
                        <th
                          scope="col"
                          class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
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
                        v-for="institution in institutions.data"
                        :key="institution.id"
                        class="transition-all hover:bg-gray-100 dark:hover:bg-gray-700 hover:shadow-lg dark:bg-gray-600"
                      >
                        <td class="px-6 py-2 whitespace-nowrap">
                          <div class="flex items-center">
                            <div
                              class="flex-shrink-0 w-10 h-10 bg-gray-200 dark:bg-gray-400 rounded-full flex justify-center items-center"
                            ></div>

                            <div class="ml-4">
                              <div
                                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                              >
                                {{ institution.name }}
                                {{
                                  institution.abbreviation
                                    ? "(" + institution.abbreviation + ")"
                                    : ""
                                }}
                              </div>
                              <div class="text-sm text-gray-500"></div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div
                            class="text-sm text-gray-900 dark:text-gray-100 text-center"
                          >
                            {{ institution.departments }}
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div
                            class="text-sm text-gray-900 dark:text-gray-100 text-center"
                          >
                            {{ institution.units }}
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div
                            class="text-sm text-gray-900 dark:text-gray-100 text-center"
                          >
                            {{ institution.divisions.toLocaleString() }}
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div
                            class="text-sm text-gray-900 dark:text-gray-100 text-center"
                          >
                            {{ institution.staff.toLocaleString() }}
                          </div>
                        </td>

                        <td
                          class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
                        >
                          <FlyoutMenu
                            @editItem="
                              ($event, id) => displayEditModal($event, id)
                            "
                            @deleteItem="
                              ($event, id) => displayDeleteModal($event, id)
                            "
                            name="edit"
                            path="institution"
                            :route_id="institution.id"
                          />
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <Pagination :records="institutions" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <Modal @close="toggleCreateModal()" :show="openCreateModal">
        <Create @formSubmitted="toggleCreateModal()" />
      </Modal>
      <Modal @close="toggleEditModal()" :show="openEditModal">
        <Edit
          @formSubmitted="toggleEditModal()"
          :selectedModel="selectedModel[0]"
        />
      </Modal>
      <Modal @close="toggleDeleteModal()" :show="openDeleteModal">
        <Delete
          @institutionDeleted="toggleDeleteModal()"
          @cancelDelete="toggleDeleteModal()"
          :selectedModel="selectedModel[0]"
        />
      </Modal>
    </div>
  </MainLayout>
</template>

<style scoped>
/* .formkit-input{
    @apply border-none ring-1 ring-green-300 dark:ring-gray-500  focus:ring-green-600 dark:focus:ring-gray-50
} */
.formkit-prefix-icon {
  @apply text-green-600 dark:text-gray-200;
}
input::placeholder {
  @apply text-gray-400;
}
</style>
