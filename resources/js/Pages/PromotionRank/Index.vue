<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from '@/Components/Pagination.vue'
import Promotion from './Promotions.vue'
defineProps({
    promotions: Object,
    filters: Object,
});
</script>

<template>
    <MainLayout>

        Promotions
        <Promotion :promotions="promotions.data" />
        <Pagination :records="promotions" />
    </MainLayout>
</template>