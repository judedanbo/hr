<script setup>
import { computed } from "vue";
import { Bar } from "vue-chartjs";
import {
	Chart as ChartJS, Title, Legend, Tooltip,
	BarElement, CategoryScale, LinearScale,
} from "chart.js";
import ChartDataLabels from "chartjs-plugin-datalabels";
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale, ChartDataLabels);
const isDark = useDark();

const props = defineProps({
	levelByGender: { type: Object, required: true },
	levelLabels: { type: Object, required: true },
	title: { type: String, default: "Highest Qualification Level by Gender" },
	expanded: { type: Boolean, default: false },
});

const orderedLevels = computed(() =>
	Object.keys(props.levelByGender).filter((lv) => {
		const entry = props.levelByGender[lv];
		return (entry.M ?? 0) + (entry.F ?? 0) > 0;
	}),
);

const chartData = computed(() => ({
	labels: orderedLevels.value.map((lv) => props.levelLabels[lv] ?? lv),
	datasets: [
		{
			label: "Male",
			data: orderedLevels.value.map((lv) => props.levelByGender[lv]?.M ?? 0),
			backgroundColor: "#3b82f6",
			borderRadius: 4,
			stack: "gender",
		},
		{
			label: "Female",
			data: orderedLevels.value.map((lv) => props.levelByGender[lv]?.F ?? 0),
			backgroundColor: "#ec4899",
			borderRadius: 4,
			stack: "gender",
		},
	],
}));

const chartOptions = computed(() => ({
	responsive: true,
	maintainAspectRatio: false,
	plugins: {
		legend: {
			position: "top",
			labels: { color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)" },
		},
		title: {
			display: true,
			text: props.title,
			color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			font: { size: 14, weight: "bold" },
		},
		tooltip: {
			callbacks: {
				label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString()}`,
				footer: (items) => {
					const total = items.reduce((sum, i) => sum + i.parsed.y, 0);
					return `Total: ${total.toLocaleString()}`;
				},
			},
		},
		datalabels: {
			color: "#fff",
			font: { weight: "bold", size: props.expanded ? 12 : 10 },
			formatter: (v) => (v > 0 ? v : ""),
		},
	},
	scales: {
		x: {
			stacked: true,
			ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" },
		},
		y: {
			stacked: true,
			beginAtZero: true,
			ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" },
		},
	},
}));
</script>

<template>
	<div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
		<div :class="expanded ? 'h-full' : 'h-80'">
			<Bar :data="chartData" :options="chartOptions" />
		</div>
	</div>
</template>
