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
	byUnit: { type: Object, required: true },
	levelLabels: { type: Object, required: true },
	title: { type: String, default: "Qualifications by Unit" },
	topN: { type: Number, default: 8 },
});

const colors = [
	"#4f46e5", "#06b6d4", "#10b981", "#84cc16", "#eab308",
	"#f97316", "#ef4444", "#ec4899", "#8b5cf6", "#64748b",
];

const topUnits = computed(() => {
	return Object.entries(props.byUnit)
		.map(([name, counts]) => ({
			name,
			total: Object.values(counts).reduce((a, b) => a + b, 0),
			counts,
		}))
		.sort((a, b) => b.total - a.total)
		.slice(0, props.topN);
});

const levelKeys = computed(() => {
	const keys = new Set();
	topUnits.value.forEach((u) =>
		Object.keys(u.counts).forEach((k) => keys.add(k))
	);
	return [...keys];
});

const chartData = computed(() => ({
	labels: topUnits.value.map((u) => u.name),
	datasets: levelKeys.value.map((lv, i) => ({
		label: props.levelLabels[lv] ?? lv,
		data: topUnits.value.map((u) => u.counts[lv] ?? 0),
		backgroundColor: colors[i % colors.length],
	})),
}));

const chartOptions = computed(() => ({
	indexAxis: "y",
	responsive: true,
	maintainAspectRatio: false,
	plugins: {
		legend: {
			position: "top",
			labels: {
				color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			},
		},
		title: {
			display: true,
			text: props.title,
			color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			font: { size: 14, weight: "bold" },
		},
	},
	scales: {
		x: {
			stacked: true,
			ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" },
		},
		y: {
			stacked: true,
			ticks: { color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)" },
		},
	},
}));
</script>

<template>
	<div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
		<div class="h-96">
			<Bar :data="chartData" :options="chartOptions" />
		</div>
	</div>
</template>
