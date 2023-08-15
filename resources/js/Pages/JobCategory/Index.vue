<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import PageHeader from "@/Components/PageHeader.vue";
import BreezeButton from "@/Components/Button.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/Modal.vue";
import InfoCard from "@/Components/InfoCard.vue";
import CategoryTable from "./partials/CategoryTable.vue";
import AddCategory from "./partials/AddCategory.vue";


let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

let props = defineProps({
  categories: Object,
  filters: Object,
});


let BreadCrumpLinks = [
  {
    name: "Ranks/Categories",
  },
];

let search = ref(props.filters.search);

watch(
  search,
  debounce(function (value) {
    Inertia.get(
      route("job-category.index"),
      { search: value },
      { preserveState: true, replace: true, preserveScroll: true }
    );
  }, 300)
);
</script>

<template>
  <Head title="Harmonized Categories" />
 
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
          <h2 class="text-3xl text-gray-900 dark:text-gray-50 mt-4">Ranks/Grades Categories</h2>
          <div class="sm:flex items-center justify-between my-2">
            <FormKit
              v-model="search"
              prefix-icon="search"
              type="search"
              placeholder="Search ranks..."
              autofocus
            />
            <InfoCard title="Ranks" :value="categories.total" />
            <BreezeButton @click="toggle()">Add New</BreezeButton>
          </div>

          <CategoryTable :categories="categories" />
        </div>
      </div>
    </div>
    <Modal @close="toggle()" :show="openAddDialog">
      <AddCategory @formSubmitted="toggle()" />
    </Modal>
  </MainLayout>
</template>
