<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { format, differenceInYears, formatDistanceStrict } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import InfoCard from "@/Components/InfoCard.vue";
import NoItem from "@/Components/NoItem.vue";
import PageHeader from "@/Components/PageHeader.vue";
import AddStaffForm from "./AddStaffForm.vue";
</script>

<template>
  <Head title="Staff" />

  <MainLayout>
    <template #header>
      <PageHeader name="Staff" />
    </template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="overflow-hidden shadow-sm sm:rounded-lg">
        Add new Staff

        <AddStaffForm />
      </div>
    </div>
  </MainLayout>
</template>
