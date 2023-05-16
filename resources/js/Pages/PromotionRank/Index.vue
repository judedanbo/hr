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
let year = ref(props.filters.year)
let month = ref(props.filters.month)

const searchStaff = (value) => {

    Inertia.get(route('promotion.batch.show', { year: year.value, month: month.value }), { search: value }, { preserveState: true, replace: true, preserveScroll: true })
}

// watch(search, (value) => {
//     Inertia.get(route('promotion.show', { year: 2022 }), { search: value }, { preserveState: true, replace: true, preserveScroll: true })
// })
</script>

<template>
    <MainLayout>
        Promotions
        <input v-model="search" type="search">
        <Promotion @update:model-value="searchStaff" :promotions="promotions.data" />
        <Pagination :records="promotions" />
    </MainLayout>
</template>