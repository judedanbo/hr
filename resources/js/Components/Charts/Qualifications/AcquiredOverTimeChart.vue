<script setup>
import { computed } from "vue";
import { Line } from "vue-chartjs";
import {
	Chart as ChartJS, Title, Legend, Tooltip,
	LineElement, PointElement, CategoryScale, LinearScale, Filler,
} from "chart.js";
import ChartDataLabels from "chartjs-plugin-datalabels";
import { useDark } from "@vueuse/core";

ChartJS.register(
	Title, Legend, Tooltip,
	LineElement, PointElement, CategoryScale, LinearScale, Filler, ChartDataLabels,
);
const isDark = useDark();

const props = defineProps({
	trend: { type: Object, required: true },
	title: { type: String, default: "Qualifications Acquired Over Time" },
	expanded: { type: Boolean, default: false },
});

const sortedYears = computed(() =>
	Object.keys(props.trend).map(Number).sort((a, b) => a - b),
);

const chartData = computed(() => ({
	labels: sortedYears.value,
	datasets: [{
		label: "Count",
		data: sortedYears.value.map((y) => props.trend[y]),
		borderColor: "#4f46e5",
		backgroundColor: "rgba(79, 70, 229, 0.15)",
		fill: true,
		tension: 0.3,
		pointRadius: 3,
	}],
}));

const chartOptions = computed(() => ({
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
		datalabels: {
			align: "top",
			color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			font: { size: props.expanded ? 11 : 9, weight: "bold" },
			formatter: (v) => (v > 0 ? v : ""),
		},
	},
	scales: {
		x: { ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" } },
		y: {
			beginAtZero: true,
			ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" },
		},
	},
}));
</script>

<template>
	<div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
		<div :class="expanded ? 'h-full' : 'h-80'">
			<Line :data="chartData" :options="chartOptions" />
		</div>
	</div>
</template>
