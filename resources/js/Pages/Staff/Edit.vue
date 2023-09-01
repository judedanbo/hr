<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import EditStaffForm from "./EditStaffForm.vue";
</script>

<template>
  <Head title="Staff" />

  <MainLayout>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="overflow-hidden shadow-sm sm:rounded-lg">
        Edit Staff

        <EditStaffForm />
      </div>
    </div>
  </MainLayout>
</template>
