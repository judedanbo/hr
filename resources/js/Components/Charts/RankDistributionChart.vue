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
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale);

const isDark = useDark();

const props = defineProps({
	distribution: {
		type: Array,
		required: true,
	},
	title: { type: String, default: "Top 10 Ranks by Staff" },
});

const emit = defineEmits(["bar-click"]);

const chartData = computed(() => ({
	labels: props.distribution.map((d) => d.name),
	datasets: [
		{
			label: "Staff Count",
			borderWidth: 0,
			borderRadius: 4,
			backgroundColor: "#8b5cf6",
			hoverBackgroundColor: "#7c3aed",
			data: props.distribution.map((d) => d.count),
		},
	],
}));

const chartOptions = computed(() => ({
	indexAxis: "y",
	responsive: true,
	maintainAspectRatio: false,
	onClick: (event, elements) => {
		if (elements.length > 0) {
			const index = elements[0].index;
			const rank = props.distribution[index];
			emit("bar-click", {
				filter: "rank",
				params: { id: rank.id },
				title: `${rank.full_name} Staff`,
			});
		}
	},
	plugins: {
		legend: {
			display: false,
		},
		title: {
			display: true,
			text: props.title,
			color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			font: {
				size: 14,
				weight: "bold",
			},
		},
		tooltip: {
			callbacks: {
				label: function (context) {
					const total = props.distribution.reduce((sum, d) => sum + d.count, 0);
					const percentage = ((context.raw / total) * 100).toFixed(1);
					return `${context.raw?.toLocaleString()} staff (${percentage}%)`;
				},
				title: function (tooltipItems) {
					const index = tooltipItems[0].dataIndex;
					return props.distribution[index].full_name;
				},
			},
		},
	},
	scales: {
		x: {
			grid: {
				color: isDark.value ? "rgba(255,255,255,0.1)" : "rgba(0,0,0,0.1)",
			},
			ticks: {
				color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)",
			},
		},
		y: {
			grid: {
				display: false,
			},
			ticks: {
				color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)",
			},
		},
	},
}));
</script>

<template>
	<div
		class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4"
	>
		<div class="h-80">
			<Bar :data="chartData" :options="chartOptions" class="cursor-pointer" />
		</div>
	</div>
</template>
