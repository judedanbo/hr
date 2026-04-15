<script setup>
import { computed } from "vue";
import { Bar } from "vue-chartjs";
import {
	Chart as ChartJS, Title, Legend, Tooltip,
	BarElement, CategoryScale, LinearScale,
} from "chart.js";
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale);
const isDark = useDark();

const props = defineProps({
	qualifications: { type: Array, required: true },
	title: { type: String, default: "Top Qualifications" },
});

const chartData = computed(() => ({
	labels: props.qualifications.map((q) => q.name),
	datasets: [{
		label: "Count",
		data: props.qualifications.map((q) => q.count),
		backgroundColor: "#8b5cf6",
		borderRadius: 4,
	}],
}));

const chartOptions = computed(() => ({
	indexAxis: "y",
	responsive: true,
	maintainAspectRatio: false,
	plugins: {
		legend: { display: false },
		title: {
			display: true,
			text: props.title,
			color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			font: { size: 14, weight: "bold" },
		},
	},
	scales: {
		x: { ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" } },
		y: { ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" } },
	},
}));
</script>

<template>
	<div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
		<div class="h-80">
			<Bar :data="chartData" :options="chartOptions" />
		</div>
	</div>
</template>
