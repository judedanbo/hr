<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { Bar } from "vue-chartjs";
import {
	Chart as ChartJS,
	Title,
	Legend,
	Tooltip,
	BarElement,
	CategoryScale,
	LinearScale,
} from "chart.js";

ChartJS.register(Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale);

const props = defineProps({
	cycles: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
	summary: { type: Object, default: () => ({ total: 0, completed: 0 }) },
	statusDistribution: { type: Array, default: () => [] },
	bandDistribution: { type: Array, default: () => [] },
	byUnit: { type: Array, default: () => [] },
});

const cycleFilter = ref(props.filters.cycle_id ?? "");

const applyFilter = () => {
	router.get(route("appraisal.report.index"), { cycle_id: cycleFilter.value || undefined }, { preserveState: true, replace: true });
};

const exportUrl = computed(() =>
	route("appraisal.report.export", cycleFilter.value ? { cycle_id: cycleFilter.value } : {}),
);

const chartOptions = {
	responsive: true,
	maintainAspectRatio: false,
	plugins: { legend: { display: false } },
};

const statusChart = computed(() => ({
	labels: props.statusDistribution.map((d) => d.label),
	datasets: [{ label: "Appraisals", backgroundColor: "#6366f1", borderRadius: 4, data: props.statusDistribution.map((d) => d.count) }],
}));

const bandChart = computed(() => ({
	labels: props.bandDistribution.map((d) => d.label),
	datasets: [{ label: "Appraisals", backgroundColor: "#10b981", borderRadius: 4, data: props.bandDistribution.map((d) => d.count) }],
}));

const links = [{ name: "Appraisal Reports", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Appraisal Reports" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />

			<div class="flex flex-wrap items-center justify-between gap-3 my-4">
				<h1 class="text-2xl font-semibold dark:text-gray-100">Appraisal Reports</h1>
				<div class="flex items-center gap-3">
					<select v-model="cycleFilter" class="rounded-md border-gray-300 dark:bg-gray-700 dark:text-gray-100" @change="applyFilter">
						<option value="">All cycles</option>
						<option v-for="cycle in cycles" :key="cycle.value" :value="cycle.value">{{ cycle.label }}</option>
					</select>
					<a :href="exportUrl" class="rounded-md bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-500">Export Excel</a>
				</div>
			</div>

			<div class="grid grid-cols-2 gap-4">
				<div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm p-5">
					<p class="text-sm text-gray-500 dark:text-gray-400">Total appraisals</p>
					<p class="text-3xl font-semibold dark:text-gray-100">{{ summary.total }}</p>
				</div>
				<div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm p-5">
					<p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
					<p class="text-3xl font-semibold text-green-600">{{ summary.completed }}</p>
				</div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
				<div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm p-5">
					<h2 class="font-semibold mb-3 dark:text-gray-100">By stage</h2>
					<div class="h-64"><Bar :data="statusChart" :options="chartOptions" /></div>
				</div>
				<div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm p-5">
					<h2 class="font-semibold mb-3 dark:text-gray-100">Rating band distribution</h2>
					<div class="h-64"><Bar :data="bandChart" :options="chartOptions" /></div>
				</div>
			</div>

			<div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm p-5 mt-4">
				<h2 class="font-semibold mb-3 dark:text-gray-100">Completion by unit</h2>
				<table class="min-w-full text-sm dark:text-gray-200">
					<thead>
						<tr class="text-left text-gray-500 dark:text-gray-400">
							<th class="py-2">Unit</th>
							<th class="py-2">Total</th>
							<th class="py-2">Completed</th>
							<th class="py-2">%</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="row in byUnit" :key="row.unit" class="border-t border-gray-100 dark:border-gray-700">
							<td class="py-2">{{ row.unit }}</td>
							<td class="py-2">{{ row.total }}</td>
							<td class="py-2">{{ row.completed }}</td>
							<td class="py-2">{{ row.total ? Math.round((row.completed / row.total) * 100) : 0 }}%</td>
						</tr>
						<tr v-if="!byUnit.length"><td colspan="4" class="py-4 text-center text-gray-500 dark:text-gray-400">No data.</td></tr>
					</tbody>
				</table>
			</div>
		</main>
	</MainLayout>
</template>
