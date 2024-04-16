<script setup>
import { ref, onMounted, computed } from "vue";
import PageHeading from "@/Components/PageHeading.vue";
import PageActions from "@/Components/PageActions.vue";
import PageStats from "@/Components/PageStats.vue";
import { Inertia } from "@inertiajs/inertia";
import { UserGroupIcon, UsersIcon } from "@heroicons/vue/24/outline";
import BaseChart from "@/Components/Charts/BaseChart.vue";
const props = defineProps({
	rank: {
		type: Number,
		required: true,
	},
	search: {
		type: String,
		default: "",
	},
});
const unitsStats = ref([]);
onMounted(async () => {
	unitsStats.value = (
		await axios.get(route("job.stats", { job: props.rank }), {
			params: { search: props.search },
		})
	).data;
});
const totalStaffCount = computed(() => {
	return unitsStats.value.total_staff_count;
});
const dueForPromotion = computed(() => {
	return unitsStats.value.due_for_promotion;
});
const currentStaffCount = computed(() => {
	return unitsStats.value.current_staff_count;
});
const genderStats = computed(() => {
	return unitsStats.value.gender_stats;
});

const stats = ref([]);
stats.value = [
	{
		id: 3,
		name: "Current Staff",
		stat: currentStaffCount,
		icon: UsersIcon,
		change: "3.2%",
		changeType: "decrease",
	},
	{
		id: 1,
		name: "All Time Staff",
		stat: totalStaffCount,
		icon: UserGroupIcon,
		change: "2",
		changeType: "increase",
	},
	{
		id: 2,
		name: "Due for promotion",
		stat: dueForPromotion,
		icon: UsersIcon,
		change: "5.4%",
		changeType: "increase",
	},
];
</script>
<template>
	<PageStats :stats="stats" />
	<div class="w-full sm:w-1/2 md:w-1/3 my-4">
		<BaseChart
			v-if="genderStats?.length > 0"
			type="bar"
			title="Staff by Gender"
			:labels="['Male', 'Female']"
			:datasets="genderStats"
		/>
	</div>
</template>
