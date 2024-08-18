<script setup>
import { ref, onMounted, computed } from "vue";
import PageStats from "@/Components/PageStats.vue";
import { UserGroupIcon, UsersIcon } from "@heroicons/vue/24/outline";
import { useDark } from "@vueuse/core";
import RankUnitList from "./RankUnitList.vue";
import { useNavigation } from "@/Composables/navigation";

const tableCols = ["units", "No. Staff"];
import { Bar, Pie } from "vue-chartjs";
import {
	Chart as ChartJS,
	Title,
	Legend,
	Tooltip,
	BarElement,
	ArcElement,
	CategoryScale,
	LinearScale,
} from "chart.js";

const navigation = computed(() => useNavigation(jobStats.value.staff));
ChartJS.register(
	Title,
	Legend,
	Tooltip,
	ArcElement,
	BarElement,
	CategoryScale,
	LinearScale,
);
const isDark = useDark();

// const chartData = ref({
// 	labels: ["January", "February", "March", "April", "May", "June", "July"],
// 	datasets: [
// 		{
// 			backgroundColor: [
// 				"red",
// 				"blue",
// 				"green",
// 				"yellow",
// 				"purple",
// 				"orange",
// 				"pink",
// 			],
// 			label: "My First Dataset",
// 			data: [65, 59, 80, 81, 56, 55, 40],
// 			fill: false,
// 			borderColor: "rgb(75, 192, 192)",
// 			tension: 0.1,
// 		},
// 	],
// });
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
const unitsStats = ref({});
const jobStats = ref([]);
onMounted(async () => {
	unitsStats.value = (
		await axios.get(route("job.stats", { job: props.rank }), {
			params: { search: props.search },
		})
	).data;
	jobStats.value = (
		await axios.get(route("job.unit-stats", { job: props.rank }))
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
const chartTitle = computed(() => {
	return unitsStats.value.name;
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
const genderData = computed(() => {
	return {
		labels: ["Male", "Female"],
		datasets: [
			{
				label: "Active Staff",
				borderWidth: 0,
				backgroundColor: ["#2563eb", "#fb7185"],
				data: [unitsStats.value.male_count, unitsStats.value.female_count],
			},
		],
	};
});
// const unitsData = computed(() => {
// 	return {
// 		unit: jobStats.value.map((unit) => unit.name),
// 		value: jobStats.value.map((unit) => unit.total_staff),
// 	};
// });
</script>
<template>
	<div>
		<PageStats :stats="stats" />
		<div class="flex mt-4 gap-x-4 items-start">
			<div class="w-1/3">
				<Pie
					class="bg-white dark:bg-gray-700 rounded-lg shadow-md"
					v-if="unitsStats != {}"
					:data="genderData"
					:options="{
						responsive: true,
						plugins: {
							legend: {
								position: 'top',
								labels: {
									color: isDark ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.8)',
								},
							},
							title: {
								display: true,
								text: unitsStats.name + ' by gender',
								color: isDark ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.6)',
							},
						},
					}"
				/>
			</div>
			<div class="flex-grow">
				<RankUnitList :units="jobStats">
					<template #pagination>
						{{ navigation }}
						<Pagination :navigation="navigation" />
					</template>
				</RankUnitList>
			</div>
			<!-- <pre>{{ unitsData }}</pre> -->
		</div>
	</div>
</template>
