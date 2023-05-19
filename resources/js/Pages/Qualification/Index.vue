<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from '@/Components/Pagination.vue'
import QualificationList from './QualificationList.vue'
import { ref, watch } from 'vue'
import { Inertia } from '@inertiajs/inertia'
let props = defineProps({
    qualifications: Object,
    filters: Object,
});

let search = ref(props.filters.search)


const searchQualification = (value) => {

    Inertia.get(route('qualification.index'), { search: value }, { preserveState: true, replace: true, preserveScroll: true })
}

</script>

<template>
    <MainLayout>
        Qualifications
        <QualificationList @update:model-value="searchQualification" :qualifications="qualifications.data" />
        <Pagination :records="qualifications" />
    </MainLayout>
</template>