<script setup>
import { computed } from "vue";
import { Doughnut } from "vue-chartjs";
import { Chart as ChartJS, Title, Legend, Tooltip, ArcElement } from "chart.js";
import ChartDataLabels from "@/lib/chart-datalabels.js";
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, ArcElement, ChartDataLabels);
const isDark = useDark();

const props = defineProps({
	distribution: { type: Object, required: true },
	labels: { type: Object, required: true },
	title: { type: String, default: "Qualification Level Distribution" },
	expanded: { type: Boolean, default: false },
	labelMode: { type: String, default: "both" },
});

const colors = [
	"#4f46e5", "#06b6d4", "#10b981", "#84cc16", "#eab308",
	"#f97316", "#ef4444", "#ec4899", "#8b5cf6", "#64748b",
];

const entries = computed(() =>
	Object.entries(props.distribution).filter(([, v]) => v > 0),
);

const total = computed(() =>
	entries.value.reduce((a, [, v]) => a + v, 0),
);

const chartData = computed(() => ({
	labels: entries.value.map(([k]) => props.labels[k] ?? k),
	datasets: [{
		data: entries.value.map(([, v]) => v),
		backgroundColor: entries.value.map((_, i) => colors[i % colors.length]),
		borderWidth: 0,
	}],
}));

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
		tooltip: {
			callbacks: {
				label: (ctx) => {
					const v = ctx.parsed;
					const pct = total.value > 0 ? ((v / total.value) * 100).toFixed(1) : 0;
					return `${ctx.label}: ${v.toLocaleString()} (${pct}%)`;
				},
			},
		},
		datalabels: {
			display: props.labelMode !== "none",
			color: "#fff",
			font: { weight: "bold", size: props.expanded ? 13 : 11 },
			formatter: (value) => {
				if (total.value === 0 || value === 0) return "";
				const pct = (value / total.value) * 100;
				if (pct < 3 && !props.expanded) return "";
				if (props.labelMode === "count") return value.toLocaleString();
				if (props.labelMode === "percent") return `${pct.toFixed(0)}%`;
				return `${value}\n${pct.toFixed(0)}%`;
			},
			textAlign: "center",
		},
	},
}));
</script>

<template>
	<div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
		<div :class="expanded ? 'h-full' : 'h-80'">
			<Doughnut :data="chartData" :options="chartOptions" />
		</div>
	</div>
</template>
