<script setup>
import { computed } from "vue";
import { Doughnut } from "vue-chartjs";
import { Chart as ChartJS, Title, Legend, Tooltip, ArcElement } from "chart.js";
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, ArcElement);
const isDark = useDark();

const props = defineProps({
	distribution: { type: Object, required: true },
	labels: { type: Object, required: true },
	title: { type: String, default: "Qualification Level Distribution" },
});

const colors = [
	"#4f46e5", "#06b6d4", "#10b981", "#84cc16", "#eab308",
	"#f97316", "#ef4444", "#ec4899", "#8b5cf6", "#64748b",
];

const chartData = computed(() => {
	const entries = Object.entries(props.distribution).filter(([, v]) => v > 0);
	return {
		labels: entries.map(([k]) => props.labels[k] ?? k),
		datasets: [{
			data: entries.map(([, v]) => v),
			backgroundColor: entries.map((_, i) => colors[i % colors.length]),
		}],
	};
});

const chartOptions = computed(() => ({
	responsive: true,
	maintainAspectRatio: false,
	plugins: {
		legend: {
			position: "right",
			labels: { color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)" },
		},
		title: {
			display: true,
			text: props.title,
			color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			font: { size: 14, weight: "bold" },
		},
	},
}));
</script>

<template>
	<div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
		<div class="h-80">
			<Doughnut :data="chartData" :options="chartOptions" />
		</div>
	</div>
</template>
