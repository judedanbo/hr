<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from '@/Components/Pagination.vue'
import Promotion from './Promotions.vue'
import { ref, watch } from 'vue'
import { Inertia } from '@inertiajs/inertia'
let props = defineProps({
    promotions: Object,
    filters: Object,
});

let search = ref(props.filters.search)

watch(search, (value) => {
    Inertia.get(route('promotion.show', { year: 2022 }), { search: value }, { preserveState: true, replace: true, preserveScroll: true })
})
</script>

<template>
    <MainLayout>
        Promotions
        <input v-model="search" type="search">
        <Promotion :promotions="promotions.data" />
        <Pagination :records="promotions" />
    </MainLayout>
</template>