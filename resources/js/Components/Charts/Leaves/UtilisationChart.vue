<script setup>
import { computed } from "vue";
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

ChartJS.register(
	Title,
	Legend,
	Tooltip,
	BarElement,
	CategoryScale,
	LinearScale,
);

const props = defineProps({
	rows: { type: Array, default: () => [] },
});

const chartData = computed(() => ({
	labels: props.rows.map((r) => r.leave_type),
	datasets: [
		{
			label: "Taken",
			backgroundColor: "#16a34a",
			borderRadius: 4,
			data: props.rows.map((r) => r.taken),
		},
		{
			label: "Remaining",
			backgroundColor: "#cbd5e1",
			borderRadius: 4,
			data: props.rows.map((r) => r.remaining),
		},
	],
}));

const chartOptions = {
	responsive: true,
	maintainAspectRatio: false,
	scales: { x: { stacked: true }, y: { stacked: true } },
	plugins: { legend: { position: "bottom" } },
};
</script>

<template>
	<div class="h-64">
		<Bar :data="chartData" :options="chartOptions" />
	</div>
</template>
