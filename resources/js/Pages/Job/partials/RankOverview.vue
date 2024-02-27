<script setup>
import { ref, onMounted, computed } from "vue";
import PageHeading from "@/Components/PageHeading.vue";
import PageActions from "@/Components/PageActions.vue";
import PageStats from "@/Components/PageStats.vue";
import { Inertia } from "@inertiajs/inertia";
import { UserGroupIcon, UsersIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    rank: {
        type: Number,
        required: true,
    },
});
const unitsStats = ref([]);
onMounted(async() => {
    unitsStats.value = (await axios.get(route("job.stats",{job: props.rank}))).data;
    console.log(unitsStats.value);
})
const totalStaffCount =  computed(() =>{
    return unitsStats.value.total_staff_count
});
const activeStaffCount =  computed(() =>{
    return unitsStats.value.active_staff_count
});
const currentStaffCount =  computed(() =>{
    return unitsStats.value.current_staff_count
});


const stats = ref([]);
stats.value = [
    { id: 3, name: 'Current Units', stat: currentStaffCount, icon: UsersIcon, change: '3.2%', changeType: 'decrease' },
    { id: 2, name: 'Active Staff', stat: activeStaffCount, icon: UsersIcon, change: '5.4%', changeType: 'increase' },
    { id: 1, name: 'All Time Staff', stat: totalStaffCount , icon: UserGroupIcon, change: '2', changeType: 'increase' },
]
</script>
<template>
    <PageStats :stats="stats" />
</template>